<?php

declare(strict_types=1);

namespace App\Components\ReadModel;

final readonly class ReadModelArray
{
    /**
     * @param list<object> $items
     * @return list<array<string, array<int|string, scalar|null>|scalar|null>>
     */
    public static function fromItems(array $items): array
    {
        return array_map(
            static fn (object $item): array => $item->toArray(),
            $items,
        );
    }
}
