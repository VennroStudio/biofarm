<?php

declare(strict_types=1);

namespace App\Components\Id;

use App\Components\Clock\UtcClock;
use DateMalformedStringException;
use Random\RandomException;

final readonly class ReadableIdGenerator
{
    /**
     * @throws DateMalformedStringException
     * @throws RandomException
     */
    public function generate(string $prefix): string
    {
        return \sprintf(
            '%s-%s-%04d',
            $prefix,
            UtcClock::now()->format('YmdHis'),
            random_int(0, 9999),
        );
    }
}
