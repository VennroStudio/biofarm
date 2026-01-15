<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use ZayMedia\Shared\Http\Middleware\AuthenticateByKey;

use function App\Components\env;

return [
    AuthenticateByKey::class => static function (ContainerInterface $container): AuthenticateByKey {
        /**
         * @psalm-suppress MixedArrayAccess
         * @var array{key:string} $config
         */
        $config = $container->get('config')['authenticate'];

        return new AuthenticateByKey($config['key']);
    },

    'config' => [
        'authenticate' => [
            'key' => env('API_KEY'),
        ],
    ],
];
