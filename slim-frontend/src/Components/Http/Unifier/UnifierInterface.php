<?php

declare(strict_types=1);

namespace App\Components\Http\Unifier;

interface UnifierInterface
{
    public function unifyOne(?int $userId, ?object $item): array;

    /**
     * @param list<object> $items
     * @return list<array<string,mixed>>
     */
    public function unify(?int $userId, array $items): array;

    public function map(object $item): array;
}
