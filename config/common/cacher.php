<?php

declare(strict_types=1);

use App\Components\Cacher\Cacher;
use App\Components\Cacher\NullCacher;
use App\Components\Cacher\RedisCacher;
use Psr\Container\ContainerInterface;

use function App\Components\env;
use function App\Components\env_bool;
use function App\Components\env_int;

$redisEnabled = env_bool('REDIS', true);

return [
    Cacher::class => static function (ContainerInterface $container): Cacher {
        $configRoot = $container->get('config');
        assert(is_array($configRoot));

        /**
         * @var array{
         *     enabled:bool,
         *     host:string,
         *     port:int,
         *     user:string,
         *     password:string
         * } $config
         */
        $config = $configRoot['cacher-redis'];

        if (!$config['enabled']) {
            return new NullCacher();
        }

        if (!extension_loaded('redis')) {
            throw new RuntimeException('REDIS=true requires the PHP redis extension. Install it or set REDIS=false.');
        }

        return new RedisCacher(
            host: $config['host'],
            port: $config['port'],
            user: $config['user'],
            password: $config['password'],
            timeout: 2
        );
    },

    'config' => [
        'cacher-redis' => [
            'enabled'  => $redisEnabled,
            'host'     => $redisEnabled ? env('REDIS_HOST') : '',
            'port'     => $redisEnabled ? env_int('REDIS_PORT') : 6379,
            'user'     => $redisEnabled ? env('REDIS_USER', '') : '',
            'password' => $redisEnabled ? env('REDIS_PASSWORD', '') : '',
        ],
    ],
];
