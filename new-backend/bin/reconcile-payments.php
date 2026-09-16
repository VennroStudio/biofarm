#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Modules\Payment\Service\PaymentService;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}
if (in_array('--help', $argv, true)) {
    echo "Usage: php bin/reconcile-payments.php [--limit=50]\nReconcile payment, refund and delivery receipt outbox operations. Requires configured environment.\n";
    exit(0);
}
$limit = 50;
foreach (array_slice($argv, 1) as $argument) {
    if (!preg_match('/^--limit=(\d+)$/D', $argument, $match) || (int)$match[1] < 1 || (int)$match[1] > 100) {
        fwrite(STDERR, "Use --limit=1..100\n");
        exit(2);
    }
    $limit = (int)$match[1];
}
$lock = fopen(sys_get_temp_dir() . '/biofarm-payment-reconcile-' . hash('sha256', dirname(__DIR__)) . '.lock', 'cb');
if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
    echo "{\"running\":true}\n";
    exit(0);
}
require dirname(__DIR__) . '/vendor/autoload.php';
try {
    $container = require dirname(__DIR__) . '/config/container.php';
    $results = $container->get(PaymentService::class)->reconcile($limit);
    $checked = count(array_filter($results, static fn (array $result): bool => ($result['checked'] ?? false) === true));
    $statuses = [];
    foreach ($results as $result) {
        $status = $result['status'] ?? (($result['checked'] ?? false) ? 'checked' : 'retry_required');
        $statuses[$status] = ($statuses[$status] ?? 0) + 1;
    }
    echo json_encode(['processed' => count($results), 'checked' => $checked, 'needsAttention' => count($results) - $checked, 'statuses' => $statuses], JSON_THROW_ON_ERROR) . "\n";
    exit($checked === count($results) ? 0 : 1);
} catch (Throwable) {
    fwrite(STDERR, "{\"error\":\"reconciliation_failed\"}\n");
    exit(1);
}
