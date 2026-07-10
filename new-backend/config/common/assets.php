<?php

declare(strict_types=1);

use App\Components\Asset\ViteManifest;
use Psr\Container\ContainerInterface;

return [
    ViteManifest::class => static function (ContainerInterface $container): ViteManifest {
        /** @var array{assets: array{manifest_path: string, build_base_path: string}} $fullConfig */
        $fullConfig = $container->get('config');
        $config = $fullConfig['assets'];

        return new ViteManifest(
            manifestPath: $config['manifest_path'],
            buildBasePath: $config['build_base_path'],
        );
    },

    'config' => [
        'assets' => [
            'manifest_path'   => __DIR__ . '/../../public/build/.vite/manifest.json',
            'build_base_path' => '/build',
        ],
    ],
];
