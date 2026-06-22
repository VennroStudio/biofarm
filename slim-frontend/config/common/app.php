<?php

declare(strict_types=1);

use App\Components\App\AppInfo;
use Psr\Container\ContainerInterface;

use function App\Components\env;

return [
    AppInfo::class => static function (ContainerInterface $container): AppInfo {
        /** @var array{app: array{name: string, env: string, version: string, timezone: string}} $fullConfig */
        $fullConfig = $container->get('config');
        $config = $fullConfig['app'];

        return new AppInfo(
            name: $config['name'],
            environment: $config['env'],
            version: $config['version'],
            timezone: $config['timezone'],
        );
    },

    'config' => [
        'app' => [
            'name'     => env('APP_NAME', 'slim-frontend'),
            'env'      => env('APP_ENV', 'dev'),
            'version'  => env('APP_VERSION', 'dev'),
            'timezone' => env('APP_TIMEZONE', 'UTC'),
        ],
    ],
];
