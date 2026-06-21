<?php

declare(strict_types=1);

namespace App\Components\ReadModel;

/**
 * @template T
 */
final readonly class ModelCountItemsResult
{
    /**
     * @param list<T> $items
     */
    public function __construct(
        public array $items,
        public int $count,
    ) {}
}
