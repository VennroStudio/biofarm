<?php

declare(strict_types=1);

use App\Components\YandexDisk\HttpYandexDiskClient;
use App\Components\YandexDisk\YandexDiskClient;
use GuzzleHttp\Client;
use Psr\Container\ContainerInterface;

use function App\Components\env;

return [
    YandexDiskClient::class => static function (ContainerInterface $container): YandexDiskClient {
        /**
         * @psalm-suppress MixedArrayAccess
         * @var array{token: string} $config
         */
        $config = $container->get('config')['yandex_disk'];

        return new HttpYandexDiskClient(
            client: $container->get(Client::class),
            token:  $config['token'],
        );
    },

    'config' => [
        'yandex_disk' => [
            'token' => env('YANDEX_DISK_TOKEN'),
        ],
    ],
];