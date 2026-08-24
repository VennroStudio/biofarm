<?php

declare(strict_types=1);

namespace App\Components\Cacher;

use Override;
use Redis;
use RuntimeException;

final class RedisCacher implements Cacher
{
    private const string TAG_PREFIX = 'tag.';
    private const string VALUE_PREFIX = 'php:';

    private readonly string $host;
    private readonly int $port;
    private readonly string $user;
    private readonly string $password;
    private readonly int $timeout;

    private ?Redis $redis = null;

    public function __construct(
        string $host,
        int $port,
        string $user,
        string $password,
        int $timeout = 0
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
        $this->timeout = $timeout;
    }

    #[Override]
    public function get(string $key): array|bool|float|int|object|string|null
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $value = $this->redis?->get($key);

        return $this->decode($value);
    }

    #[Override]
    public function set(string $key, array|bool|float|int|object|string|null $value, ?int $ttl = null): bool
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $encoded = $this->encode($value);

        return $ttl === null
            ? (bool)$this->redis?->set($key, $encoded)
            : (bool)$this->redis?->set($key, $encoded, $ttl);
    }

    #[Override]
    public function setTagged(string $key, array|bool|float|int|object|string|null $value, int $ttl, array $tags): bool
    {
        $stored = $this->set($key, $value, $ttl);

        if (!$stored) {
            return false;
        }

        foreach ($tags as $tag) {
            $this->addTag($tag, $key, $ttl);
        }

        return true;
    }

    #[Override]
    public function delete(string $key): void
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $this->redis?->del($key);
    }

    #[Override]
    public function deleteTag(string $tag): void
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $tagKey = $this->tagKey($tag);
        foreach ($this->sMembers($tagKey) as $key) {
            $this->redis?->del($key);
        }

        $this->redis?->del($tagKey);
    }

    #[Override]
    public function expire(string $key, int $ttl): void
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $this->redis?->expire($key, $ttl);
    }

    #[Override]
    public function mGet(array $keys): array
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $result = $this->redis?->mGet($keys);

        if (!\is_array($result)) {
            return [];
        }

        return array_map($this->decode(...), $result);
    }

    #[Override]
    public function zAdd(string $key, float $score, float|int|string $value): void
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $this->redis?->zAdd($key, $score, $value);
    }

    #[Override]
    public function zRangeByScore(
        string $key,
        int $min,
        int $max,
        ?int $offset = null,
        ?int $count = null
    ): array {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $options = [];

        if (null !== $offset && null !== $count) {
            $options = [
                'limit' => [$offset, $count],
            ];
        }

        $result = $this->redis?->zRangeByScore(
            key: $key,
            start: (string)$min,
            end: (string)$max,
            options: $options
        );

        if (!\is_array($result)) {
            return [];
        }

        return $result;
    }

    #[Override]
    public function zRevRangeByScore(
        string $key,
        int $max,
        int $min,
        ?int $offset = null,
        ?int $count = null
    ): array {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $options = [];

        if (null !== $offset && null !== $count) {
            $options = [
                'limit' => [$offset, $count],
            ];
        }

        /** @var array|Redis $result */
        $result = $this->redis?->zRevRangeByScore($key, (string)$max, (string)$min, $options);

        if (!\is_array($result)) {
            return [];
        }

        return $result;
    }

    #[Override]
    public function increase(string $key, int $value): void
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $this->redis?->incrBy($key, $value);
    }

    #[Override]
    public function decrease(string $key, int $value): void
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $this->redis?->decrBy($key, $value);
    }

    #[Override]
    public function sAdd(string $key, string $value): void
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $this->redis?->sAdd($key, $value);
    }

    #[Override]
    public function sMembers(string $key): array
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $result = $this->redis?->sMembers($key);

        if (!\is_array($result)) {
            return [];
        }

        $rawMembers = array_values($result);
        $members = [];

        /** @psalm-suppress MixedAssignment Redis extension stubs expose set members as mixed. */
        foreach ($rawMembers as $member) {
            if (\is_string($member)) {
                $members[] = $member;
            }
        }

        return $members;
    }

    private function addTag(string $tag, string $key, int $ttl): void
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $tagKey = $this->tagKey($tag);

        $this->redis?->sAdd($tagKey, $key);
        $this->redis?->expire($tagKey, $ttl);
    }

    private function connect(): void
    {
        $redis = new Redis();

        if (!$redis->connect($this->host, $this->port, $this->timeout)) {
            throw new RuntimeException(\sprintf('Cannot connect to Redis at %s:%d.', $this->host, $this->port));
        }

        if ($this->password !== '') {
            $auth = $this->user !== ''
                ? [$this->user, $this->password]
                : $this->password;

            if ($redis->auth($auth) === false) {
                throw new RuntimeException('Cannot authenticate Redis connection.');
            }
        }

        $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);

        $this->redis = $redis;
    }

    private function isConnected(): bool
    {
        return $this->redis?->isConnected() === true;
    }

    private function tagKey(string $tag): string
    {
        return self::TAG_PREFIX . $tag;
    }

    private function encode(array|bool|float|int|object|string|null $value): string
    {
        return self::VALUE_PREFIX . serialize($value);
    }

    private function decode(mixed $value): array|bool|float|int|object|string|null
    {
        if ($value === false || $value === null) {
            return null;
        }

        if (!\is_string($value)) {
            return \is_array($value) || \is_bool($value) || \is_float($value) || \is_int($value) || \is_object($value)
                ? $value
                : null;
        }

        if (!str_starts_with($value, self::VALUE_PREFIX)) {
            return $value;
        }

        /** @psalm-suppress MixedAssignment unserialize returns mixed by design; the value is normalized below. */
        $decoded = @unserialize(substr($value, \strlen(self::VALUE_PREFIX)), ['allowed_classes' => true]);

        return \is_array($decoded) || \is_bool($decoded) || \is_float($decoded) || \is_int($decoded) || \is_object($decoded) || \is_string($decoded) || $decoded === null
            ? $decoded
            : null;
    }
}
