<?php

declare(strict_types=1);

use App\Components\App\AppInfo;
use Psr\Container\ContainerInterface;
use Slim\App;

date_default_timezone_set('UTC');
http_response_code(500);

require __DIR__ . '/../vendor/autoload.php';

/** @var ContainerInterface $container */
$container = require __DIR__ . '/../config/container.php';

/** @var AppInfo $appInfo */
$appInfo = $container->get(AppInfo::class);
if ($appInfo->timezone === '') {
    throw new RuntimeException('APP_TIMEZONE must not be empty.');
}
date_default_timezone_set($appInfo->timezone);

/** @var App $app */
$app = (require __DIR__ . '/../config/app.php')($container);
$app->run();
