<?php

declare(strict_types=1);

namespace App\Components\ReadModel;

/**
 * @psalm-type ReadModelValue = bool|int|float|string|null|array<array-key, bool|int|float|string|null|array<array-key, bool|int|float|string|null>>
 */
trait FromRowsTrait
{
    /**
     * @param list<array<string, ReadModelValue>> $rows
     * @return list<static>
     */
    public static function fromRows(array $rows): array
    {
        return array_map(static::fromRow(...), $rows);
    }
}
