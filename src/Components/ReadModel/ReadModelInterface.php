<?php

declare(strict_types=1);

namespace App\Components\ReadModel;

/**
 * @psalm-type ReadModelValue = bool|int|float|string|null|array<array-key, bool|int|float|string|null|array<array-key, bool|int|float|string|null>>
 */
interface ReadModelInterface
{
    /**
     * @return array<string, string>
     */
    public static function fields(): array;

    /**
     * @param array<string, ReadModelValue> $row
     */
    public static function fromRow(array $row): self;

    /**
     * @param list<array<string, ReadModelValue>> $rows
     * @return list<self>
     */
    public static function fromRows(array $rows): array;

    public function getId(): int|string;

    /**
     * @return array<string, ReadModelValue>
     */
    public function toArray(): array;
}
