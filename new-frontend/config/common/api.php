<?php

declare(strict_types=1);

use App\Components\Api\ApiClient;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpClient\HttpClient;

use function App\Components\env;
use function App\Components\env_float;

return [
    ApiClient::class => static function (ContainerInterface $container): ApiClient {
        /** @var array{api: array{base_url: string, timeout: float}} $fullConfig */
        $fullConfig = $container->get('config');
        $config = $fullConfig['api'];

        return new ApiClient(
            httpClient: HttpClient::create([
                'timeout' => $config['timeout'],
            ]),
            baseUrl: $config['base_url'],
        );
    },

    'config' => [
        'api' => [
            'base_url' => rtrim(env('API_BASE_URL', 'https://fakeapi.net'), '/'),
            'timeout'  => env_float('API_TIMEOUT', 5.0),
        ],
    ],
];
