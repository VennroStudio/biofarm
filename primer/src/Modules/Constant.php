<?php

declare(strict_types=1);

namespace App\Modules;

class Constant
{
    public const SPOTIFY_COUNT_WORKERS = 5;
    public const SPOTIFY_CHECK_COUNT_WORKERS = 14;
    public const SPOTIFY_ADDITIONAL_COUNT_WORKERS = 5;
    public const TIDAL_COUNT_WORKERS = 5;
    public const TIDAL_CHECK_COUNT_WORKERS = 10;
    public const APPLE_COUNT_WORKERS = 10;
    public const APPLE_CHECK_COUNT_WORKERS = 5;
    public const MAPPER_COUNT_WORKERS = 3;
    public const MAPPER_CHECK_COUNT_WORKERS = 21;
    public const LOADER_COUNT_WORKERS = 6;
    public const SYNCHRONIZE_COUNT_WORKERS = 1;
    public const RATE_ARTIST_COUNT_WORKERS = 3;
    public const LYRICS_INTERVAL_CHECKING = 7 * 24 * self::HOUR;
    public const RELOAD_CONTAINER_INTERVAL = 8 * self::HOUR;
    public const RELOAD_CONTAINER_INTERVAL_4 = 4 * self::HOUR;
    public const RELOAD_CONTAINER_INTERVAL_10 = 10 * self::HOUR;
    public const SLEEP_NO_ACCESS_TOKEN = 5 * self::MINUTE;
    private const HOUR = 3600;
    private const MINUTE = 60;

    public static function timeFrom(): int
    {
        return strtotime(date('Y-m-d 01:35:00'));
    }
}
