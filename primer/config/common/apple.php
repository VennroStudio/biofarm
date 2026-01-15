<?php

declare(strict_types=1);

use App\Components\AppleGrab\AppleGrab;
use Psr\Container\ContainerInterface;

use function App\Components\env;

return [
    AppleGrab::class => static function (ContainerInterface $container): AppleGrab {
        /**
         * @psalm-suppress MixedArrayAccess
         * @var array{
         *     teamId:string,
         *     keyId:string,
         *     keyFile:string
         * } $config
         */
        $config = $container->get('config')['apple'];

        $appleGrab = new AppleGrab(
            teamId: $config['teamId'],
            keyId: $config['keyId'],
            keyFile: $config['keyFile'],
        );

        $appleGrab->setCountryCode('US');
        $appleGrab->setDelay(10);

        return $appleGrab;
    },

    'config' => [
        'apple' => [
            'teamId' => env('APPLE_TEAM_ID'),
            'keyId' => env('APPLE_KEY_ID'),
            'keyFile' => env('APPLE_KEY_PATH'),
        ],
    ],
];
