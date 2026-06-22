<?php

declare(strict_types=1);

namespace App\Components\Api;

final readonly class ApiResponse
{
    /**
     * @template TItem of array<array-key, mixed>
     * @template TModel of object
     * @param list<TItem> $items
     * @param callable(TItem): TModel $factory
     * @return list<TModel>
     */
    public static function fromArrayList(array $items, callable $factory): array
    {
        $result = [];

        foreach ($items as $item) {
            $result[] = $factory($item);
        }

        return $result;
    }
}
