<?php

declare(strict_types=1);

use App\Components\Cacher\Cacher;
use App\Components\Cacher\RedisCacher;
use Psr\Container\ContainerInterface;

use function App\Components\env;
use function App\Components\env_int;

return [
    Cacher::class => static function (ContainerInterface $container): RedisCacher {
        $configRoot = $container->get('config');
        \assert(\is_array($configRoot));

        /**
         * @var array{
         *     host:string,
         *     port:int,
         *     password:string
         * } $config
         */
        $config = $configRoot['cacher-redis'];

        return new RedisCacher(
            host: $config['host'],
            port: $config['port'],
            password: $config['password'],
            timeout: 2
        );
    },

    'config' => [
        'cacher-redis' => [
            'host'     => env('REDIS_HOST'),
            'port'     => env_int('REDIS_PORT'),
            'password' => env('REDIS_PASSWORD'),
        ],
    ],
];
