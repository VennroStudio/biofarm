<?php

declare(strict_types=1);

namespace App\Modules\Page\Query\Page\GetBySystemKey;

final readonly class PageGetBySystemKeyQuery
{
    public function __construct(
        public string $systemKey,
    ) {}
}
