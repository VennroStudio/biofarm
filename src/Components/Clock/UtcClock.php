<?php

declare(strict_types=1);

namespace App\Components\Clock;

use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;

final class UtcClock
{
    private const string UTC = 'UTC';

    /**
     * @throws DateMalformedStringException
     */
    public static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', self::timezone());
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function fromString(string $datetime): DateTimeImmutable
    {
        return new DateTimeImmutable($datetime, self::timezone());
    }

    public static function fromTimestamp(int $timestamp): DateTimeImmutable
    {
        return new DateTimeImmutable('now', self::timezone())->setTimestamp($timestamp);
    }

    private static function timezone(): DateTimeZone
    {
        return new DateTimeZone(self::UTC);
    }
}
