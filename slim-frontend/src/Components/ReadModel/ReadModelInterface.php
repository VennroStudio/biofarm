<?php

declare(strict_types=1);

namespace App\Components\ReadModel;

interface ReadModelInterface
{
    /**
     * @return array<string, string>
     */
    public static function fields(): array;

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self;

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<self>
     */
    public static function fromRows(array $rows): array;

    public function getId(): int;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
