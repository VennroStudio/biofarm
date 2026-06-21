<?php

declare(strict_types=1);

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

use function App\Components\env;

return [
    LoggerInterface::class => static function (ContainerInterface $container): LoggerInterface {
        /** @var array{logger: array{debug: bool, stderr: bool, file: string|null}} $fullConfig */
        $fullConfig = $container->get('config');
        $config = $fullConfig['logger'];

        $level = $config['debug'] ? Level::Debug : Level::Info;
        $log = new Logger('API');

        if ($config['stderr']) {
            $log->pushHandler(new StreamHandler('php://stderr', $level));
        }

        if ($config['file'] !== null && $config['file'] !== '') {
            $log->pushHandler(new StreamHandler($config['file'], $level));
        }

        return $log;
    },

    'config' => [
        'logger' => [
            'debug'  => (bool)env('APP_DEBUG'),
            'file'   => null,
            'stderr' => true,
        ],
    ],
];
