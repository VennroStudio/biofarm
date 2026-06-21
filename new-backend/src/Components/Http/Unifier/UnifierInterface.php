<?php

declare(strict_types=1);

namespace App\Components\Http\Unifier;

/**
 * @psalm-type UnifierValue = bool|int|float|string|null|array<array-key, bool|int|float|string|null|array<array-key, bool|int|float|string|null>>
 */
interface UnifierInterface
{
    public function unifyOne(?int $userId, ?object $item): array;

    /**
     * @param list<object> $items
     * @return list<array<string, UnifierValue>>
     */
    public function unify(?int $userId, array $items): array;

    public function map(object $item): array;
}
