<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Extension\ExtensionInterface;
use Twig\Loader\FilesystemLoader;
use ZayMedia\Shared\Components\FeatureToggle\FeatureFlagTwigExtension;
use ZayMedia\Shared\Components\Frontend\FrontendUrlTwigExtension;
use ZayMedia\Shared\Components\Translator\TranslatorTwigExtension;

use function App\Components\env;

return [
    Environment::class => static function (ContainerInterface $container): Environment {
        /**
         * @psalm-suppress MixedArrayAccess
         * @var array{
         *     debug:bool,
         *     template_dirs:array<string,string>,
         *     cache_dir:string,
         *     extensions:string[],
         * } $config
         */
        $config = $container->get('config')['twig'];

        $loader = new FilesystemLoader();

        foreach ($config['template_dirs'] as $alias => $dir) {
            $loader->addPath($dir, $alias);
        }

        $environment = new Environment($loader, [
            'cache' => $config['debug'] ? false : $config['cache_dir'],
            'debug' => $config['debug'],
            'strict_variables' => $config['debug'],
            'auto_reload' => $config['debug'],
        ]);

        if ($config['debug']) {
            $environment->addExtension(new DebugExtension());
        }

        foreach ($config['extensions'] as $class) {
            /** @var ExtensionInterface $extension */
            $extension = $container->get($class);
            $environment->addExtension($extension);
        }

        return $environment;
    },

    'config' => [
        'twig' => [
            'debug' => (bool)env('APP_DEBUG', '0'),
            'template_dirs' => [
                FilesystemLoader::MAIN_NAMESPACE => __DIR__ . '/../../templates',
            ],
            'cache_dir' => __DIR__ . '/../../var/cache/twig',
            'extensions' => [
                FrontendUrlTwigExtension::class,
                FeatureFlagTwigExtension::class,
                TranslatorTwigExtension::class,
            ],
        ],
    ],
];
