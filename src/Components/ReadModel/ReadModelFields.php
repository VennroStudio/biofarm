<?php

declare(strict_types=1);

namespace App\Components\ReadModel;

final readonly class ReadModelFields
{
    /**
     * @param array<string, string> $fields
     * @return list<string>
     */
    public static function select(array $fields, ?string $alias = null): array
    {
        $select = [];

        foreach ($fields as $name => $column) {
            $select[] = self::column($column, $alias) . ' AS ' . $name;
        }

        return $select;
    }

    private static function column(string $column, ?string $alias): string
    {
        if ($alias === null || str_contains($column, '.') || str_contains($column, '(') || str_contains($column, ' ')) {
            return $column;
        }

        return $alias . '.' . $column;
    }
}
