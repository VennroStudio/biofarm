<?php

declare(strict_types=1);

namespace App\Components\Cacher;

use Override;

final class NullCacher implements Cacher
{
    #[Override]
    public function get(string $key): array|bool|float|int|object|string|null
    {
        return null;
    }

    #[Override]
    public function set(string $key, array|bool|float|int|object|string|null $value, ?int $ttl = null): bool
    {
        return false;
    }

    #[Override]
    public function setTagged(string $key, array|bool|float|int|object|string|null $value, int $ttl, array $tags): bool
    {
        return false;
    }

    #[Override]
    public function delete(string $key): void {}

    #[Override]
    public function deleteTag(string $tag): void {}

    #[Override]
    public function expire(string $key, int $ttl): void {}

    #[Override]
    public function mGet(array $keys): array
    {
        return array_fill(0, \count($keys), null);
    }

    #[Override]
    public function zAdd(string $key, float $score, float|int|string $value): void {}

    #[Override]
    public function zRangeByScore(string $key, int $min, int $max, ?int $offset = null, ?int $count = null): array
    {
        return [];
    }

    #[Override]
    public function zRevRangeByScore(string $key, int $max, int $min, ?int $offset = null, ?int $count = null): array
    {
        return [];
    }

    #[Override]
    public function increase(string $key, int $value): void {}

    #[Override]
    public function decrease(string $key, int $value): void {}

    #[Override]
    public function sAdd(string $key, string $value): void {}

    #[Override]
    public function sMembers(string $key): array
    {
        return [];
    }
}
