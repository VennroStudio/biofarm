<?php

declare(strict_types=1);

namespace App\Components\Cacher;

interface Cacher
{
    public function get(string $key): mixed;

    public function set(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * @param list<string> $tags
     */
    public function setTagged(string $key, mixed $value, int $ttl, array $tags): bool;

    public function delete(string $key): void;

    public function deleteTag(string $tag): void;

    public function expire(string $key, int $ttl): void;

    /**
     * @param array<string> $keys
     */
    public function mGet(array $keys): array;

    public function zAdd(string $key, float $score, float|int|string $value): void;

    public function zRangeByScore(
        string $key,
        int $min,
        int $max,
        ?int $offset = null,
        ?int $count = null
    ): array;

    public function zRevRangeByScore(
        string $key,
        int $max,
        int $min,
        ?int $offset = null,
        ?int $count = null
    ): array;

    public function increase(string $key, int $value): void;

    public function decrease(string $key, int $value): void;

    public function sAdd(string $key, string $value): void;

    /**
     * @return list<string>
     */
    public function sMembers(string $key): array;
}
