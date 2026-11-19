#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Modules\Ozon\OzonCatalogSync;
use App\Modules\Ozon\OzonConnection;

use function App\Components\env;

if (PHP_SAPI !== 'cli') {
    exit(1);
}
if (array_diff(array_slice($argv, 1), ['--watch', '--help'])) {
    fwrite(STDERR, "Use --watch or --help\n");
    exit(2);
}
if (in_array('--help', $argv, true)) {
    echo "php bin/sync-ozon.php [--watch]\nProcess manual requests, daily Ozon catalog sync and stock notifications.\n";
    exit(0);
}

require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$container = require dirname(__DIR__) . '/config/container.php';
if (!$container->get(OzonConnection::class)->configured()) {
    exit(0);
}
$zone = new DateTimeZone(env('OZON_SYNC_TIMEZONE', 'Asia/Krasnoyarsk'));
$watch = in_array('--watch', $argv, true);
do {
    try {
        if (!$container->get(OzonCatalogSync::class)->tick(new DateTimeImmutable('now', $zone)) && !$watch) {
            exit(1);
        }
    } catch (Throwable $exception) {
        fwrite(STDERR, 'Ozon worker could not complete the tick (' . $exception::class . ").\n");
        if (!$watch) {
            exit(1);
        }
        // Rebuild the DB connection after a failed tick, including a dropped tunnel.
        $container = require dirname(__DIR__) . '/config/container.php';
    }
    if ($watch) {
        sleep(30);
    }
} while ($watch);
