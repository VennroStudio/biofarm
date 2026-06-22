<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;

use function App\Components\env;

return [
    Environment::class => static function (ContainerInterface $container): Environment {
        /** @var array{twig: array{
         *     debug: bool,
         *     template_dirs: array<string, string>,
         *     cache_dir: string,
         * },
         * site: array{
         *     brand: array{mark: string, title: string, subtitle: string},
         *     navigation: list<array{label: string, href: string, external?: bool}>,
         *     footer: array{
         *         title: string,
         *         description: string,
         *         links: list<array{label: string, href: string, external?: bool}>
         *     }
         * }} $fullConfig
         */
        $fullConfig = $container->get('config');
        $config = $fullConfig['twig'];
        $debug = $config['debug'];

        $loader = new FilesystemLoader();
        foreach ($config['template_dirs'] as $alias => $dir) {
            $loader->addPath($dir, $alias);
        }

        $environment = new Environment($loader, [
            'cache'            => $debug ? false : $config['cache_dir'],
            'debug'            => $debug,
            'strict_variables' => $debug,
            'auto_reload'      => $debug,
        ]);

        if ($debug) {
            $environment->addExtension(new DebugExtension());
        }

        $environment->addGlobal('site', $fullConfig['site']);

        return $environment;
    },

    'config' => [
        'twig' => [
            'debug'         => (bool)env('APP_DEBUG', '1'),
            'template_dirs' => [
                FilesystemLoader::MAIN_NAMESPACE => __DIR__ . '/../../templates',
            ],
            'cache_dir' => __DIR__ . '/../../var/cache/twig',
        ],
    ],
];
