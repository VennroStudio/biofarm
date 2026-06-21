<?php

declare(strict_types=1);

namespace App\Components\ReadModel;

trait FromRowsTrait
{
    /**
     * @param list<array<string, mixed>> $rows
     * @return list<static>
     */
    public static function fromRows(array $rows): array
    {
        return array_map(static::fromRow(...), $rows);
    }
}
