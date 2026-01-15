<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Symfony\Component\HttpClient\HttplugClient;
use Typesense\Client;

use function App\Components\env;

return [
    Client::class => static function (ContainerInterface $container): Client {
        /**
         * @psalm-suppress MixedArrayAccess
         * @var array{
         *     apiKey:string,
         *     host:string,
         *     port:integer,
         *     protocol:string,
         * } $config
         */
        $config = $container->get('config')['typesense'];

        return new Client([
            'api_key' => $config['apiKey'],
            'nodes'   => [[
                'host' => $config['host'],
                'port' => $config['port'],
                'protocol' => $config['protocol'],
            ]],
            'client' => new HttplugClient(),
        ]);
    },

    'config' => [
        'typesense' => [
            'apiKey' => env('TYPESENSE_API_KEY'),
            'host' => env('TYPESENSE_HOST'),
            'port' => (int)env('TYPESENSE_PORT'),
            'protocol' => env('TYPESENSE_PROTOCOL'),
        ],
    ],
];
