<?php

declare(strict_types=1);

namespace App\Components\Cacher;

use Override;
use Redis;

class RedisCacher implements Cacher
{
    private const string TAG_PREFIX = 'tag.';

    private readonly string $host;
    private readonly int $port;
    private readonly string $password;
    private readonly int $timeout;

    private ?Redis $redis = null;

    public function __construct(
        string $host,
        int $port,
        string $password,
        int $timeout = 0
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->password = $password;
        $this->timeout = $timeout;
    }

    #[Override]
    public function get(string $key): mixed
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $value = $this->redis?->get($key);

        return $value === false ? null : $value;
    }

    #[Override]
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        /** @psalm-suppress MixedArgument */
        return (bool)$this->redis?->set($key, $value, $ttl);
    }

    #[Override]
    public function setTagged(string $key, mixed $value, int $ttl, array $tags): bool
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

    private function addTag(string $tag, string $key, int $ttl): void
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $tagKey = $this->tagKey($tag);

        $this->redis?->sAdd($tagKey, $key);
        $this->redis?->expire($tagKey, $ttl);
    }

    #[Override]
    public function deleteTag(string $tag): void
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $tagKey = $this->tagKey($tag);
        $keys = $this->redis?->sMembers($tagKey);

        if (\is_array($keys)) {
            foreach ($keys as $key) {
                if (\is_string($key)) {
                    $this->redis?->del($key);
                }
            }
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

        return $result;
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

        return array_values(array_filter($result, static fn(mixed $value): bool => \is_string($value)));
    }

    private function connect(): void
    {
        $this->redis = new Redis();
        $this->redis->connect($this->host, $this->port, $this->timeout);

        if ($this->password !== '') {
            $this->redis->auth($this->password);
        }

        $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
    }

    private function isConnected(): bool
    {
        return null !== $this->redis;
    }

    private function tagKey(string $tag): string
    {
        return self::TAG_PREFIX . $tag;
    }
}
