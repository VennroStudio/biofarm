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
        /** @var array{logging: array{channel: string, level: string, stream: string}} $fullConfig */
        $fullConfig = $container->get('config');
        $config = $fullConfig['logging'];

        $logger = new Logger($config['channel']);
        $logger->pushHandler(new StreamHandler(
            stream: $config['stream'],
            level: match (strtolower($config['level'])) {
                'debug'     => Level::Debug,
                'info'      => Level::Info,
                'notice'    => Level::Notice,
                'warning'   => Level::Warning,
                'error'     => Level::Error,
                'critical'  => Level::Critical,
                'alert'     => Level::Alert,
                'emergency' => Level::Emergency,
                default     => throw new RuntimeException('LOG_LEVEL must be a valid PSR-3 log level.'),
            },
        ));

        return $logger;
    },

    'config' => [
        'logging' => [
            'channel' => env('LOG_CHANNEL', 'slim-frontend'),
            'level'   => env('LOG_LEVEL', 'info'),
            'stream'  => env('LOG_STREAM', 'php://stderr'),
        ],
    ],
];
