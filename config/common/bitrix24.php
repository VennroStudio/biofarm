<?php

declare(strict_types=1);

use App\Components\Integration\Credential\SecretCipher;
use Psr\Container\ContainerInterface;

return [
    SecretCipher::class => static function (ContainerInterface $container): SecretCipher {
        /** @var array{security: array{app_secret: string}} $config */
        $config = $container->get('config');

        return new SecretCipher($config['security']['app_secret']);
    },
];
