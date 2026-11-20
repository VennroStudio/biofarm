<?php

declare(strict_types=1);
use App\Modules\Program\Service\ProgramService;

require dirname(__DIR__) . '/vendor/autoload.php';
if (getenv('APP_ENV') !== 'dev') {
    throw new RuntimeException('Local development only');
}
if (in_array('--worker', $argv, true)) {
    $c = require dirname(__DIR__) . '/config/container.php';
    $p = $c->get(ProgramService::class);
    for ($i = 0; $i < 20; ++$i) {
        $p->atomic(static fn () => $p->settings());
    }
    exit(0);
}
$workers = [];
for ($i = 0; $i < 6; ++$i) {
    $pipes = [];
    $process = proc_open([PHP_BINARY, __FILE__, '--worker'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) {
        throw new RuntimeException('Cannot start test worker');
    }
    fclose($pipes[0]);
    $workers[] = [$process, $pipes];
}
foreach ($workers as [$process,$pipes]) {
    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0) {
        throw new RuntimeException($out . $err);
    }
}
echo "PASS concurrency: 6 simultaneous workers, 120 financial transactions\n";
