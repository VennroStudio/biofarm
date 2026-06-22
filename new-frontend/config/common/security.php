<?php

declare(strict_types=1);

use App\Components\Security\CsrfToken;
use Psr\Container\ContainerInterface;

use function App\Components\env;

$defaultAppSecret = 'change-me-in-local-development-secret';

return [
    CsrfToken::class => static function (ContainerInterface $container) use ($defaultAppSecret): CsrfToken {
        /** @var array{security: array{app_env: string, app_secret: string}} $fullConfig */
        $fullConfig = $container->get('config');
        $config = $fullConfig['security'];

        if ($config['app_env'] === 'prod' && $config['app_secret'] === $defaultAppSecret) {
            throw new RuntimeException('APP_SECRET must be set in production.');
        }

        if (strlen($config['app_secret']) < 24) {
            throw new RuntimeException('APP_SECRET must be at least 24 characters long.');
        }

        return new CsrfToken($config['app_secret']);
    },

    'config' => [
        'security' => [
            'app_env'    => env('APP_ENV', 'dev'),
            'app_secret' => env('APP_SECRET', $defaultAppSecret),
        ],
    ],
];
