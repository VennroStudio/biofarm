#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Modules\Ozon\OzonConnection;
use App\Modules\Ozon\OzonFulfillment;
use App\Modules\Ozon\OzonPickupDirectory;
use App\Modules\Payment\Service\PaymentService;

if (PHP_SAPI !== 'cli') {
    exit(1);
}
$options = array_slice($argv, 1);
if ($options === ['--help']) {
    echo "php bin/process-ozon-orders.php [--watch | --refresh-points]\n";
    exit;
}
if (count($options) > 1 || array_diff($options, ['--watch', '--refresh-points']) !== []) {
    exit(2);
}
require dirname(__DIR__) . '/vendor/autoload.php';
$container = require dirname(__DIR__) . '/config/container.php';
$watch = $options === ['--watch'];
do {
    try {
        if ($options === ['--refresh-points']) {
            if (!$container->get(OzonConnection::class)->deliveryConnected()) {
                throw new RuntimeException('Доступ к доставке Ozon не настроен.');
            }
            echo $container->get(OzonPickupDirectory::class)->refresh(), " пунктов выдачи обновлено.\n";
            exit;
        }
        $container->get(PaymentService::class)->reconcile();
        $container->get(OzonFulfillment::class)->tick();
    } catch (Throwable $e) {
        fwrite(STDERR, 'Не удалось выполнить сверку заказов Ozon (' . $e::class . ").\n");
        if (!$watch) {
            exit(1);
        }
    }
    if ($watch) {
        sleep(15);
    }
} while ($watch);
