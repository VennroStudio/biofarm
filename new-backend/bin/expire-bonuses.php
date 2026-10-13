#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Modules\Program\Service\ProgramService;

if (PHP_SAPI !== 'cli') exit(1);
if (array_diff(array_slice($argv, 1), ['--watch', '--help'])) { fwrite(STDERR, "Use --watch or --help\n"); exit(2); }
if (in_array('--help', $argv, true)) { echo "php bin/expire-bonuses.php [--watch]\nExpire shopping credits under the finance mutex. --watch repeats every five minutes.\n"; exit(0); }
require dirname(__DIR__) . '/vendor/autoload.php';
$watch = in_array('--watch', $argv, true);
$program = (require dirname(__DIR__) . '/config/container.php')->get(ProgramService::class);
do {
    try {
        $after=0; $count=0;
        do { $batch=$program->expireShoppingBonuses($after); $count+=$batch['processed']; $after=$batch['lastUser']; } while ($after !== null);
        echo json_encode(['checkedUsers'=>$count,'completedAt'=>gmdate('c')],JSON_THROW_ON_ERROR) . "\n";
    } catch (Throwable) {
        fwrite(STDERR, "Bonus expiration failed; retry required.\n");
        if (!$watch) exit(1);
    }
    if ($watch) sleep(300);
} while ($watch);
