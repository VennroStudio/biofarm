<?php

declare(strict_types=1);

use App\Components\Frontend\FrontendUrlGenerator;
use App\Components\Frontend\FrontendUrlType;
use Psr\Container\ContainerInterface;

use function App\Components\env;

return [
    FrontendUrlGenerator::class => static function (ContainerInterface $container): FrontendUrlGenerator {
        /** @var array{frontend: array{urls: array<string, string>}} $fullConfig */
        $fullConfig = $container->get('config');
        $config = $fullConfig['frontend'];

        return new FrontendUrlGenerator(array_map(
            static fn (string $url): string => rtrim($url, '/'),
            $config['urls']
        ));
    },

    'config' => [
        'frontend' => [
            'urls' => [
                FrontendUrlType::MAIN->value => env('FRONTEND_URL'),
                FrontendUrlType::AUTH->value => env('FRONTEND_AUTH_URL'),
            ],
        ],
    ],
];
