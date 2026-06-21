<?php

declare(strict_types=1);

namespace App\Components\Http\Unifier;

final class UnifierHelper
{
    public static function withTimestamps(array $data, object $item): array
    {
        if (isset($item->createdAt)) {
            $data['created_at'] = $item->createdAt;
        }
        if (isset($item->updatedAt)) {
            $data['updated_at'] = $item->updatedAt;
        }
        return $data;
    }

    public static function toArrayWithout(object $item, string ...$keys): array
    {
        $data = $item->toArray();
        foreach ($keys as $key) {
            unset($data[$key]);
        }
        return $data;
    }

    public static function transformField(array $data, string $field, callable $transform): array
    {
        $data[$field] = $transform($data[$field]);
        return $data;
    }
}