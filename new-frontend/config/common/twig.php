<?php

declare(strict_types=1);

use App\Components\Asset\ViteManifest;
use App\Components\Security\CsrfToken;
use App\Components\Twig\FormattingExtension;
use Psr\Container\ContainerInterface;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

use function App\Components\env_bool;

return [
    Environment::class => static function (ContainerInterface $container): Environment {
        /** @var array{twig: array{
         *     debug: bool,
         *     template_dirs: array<string, string>,
         *     cache_dir: string,
         * },
         * site: array{
         *     locale: string,
         *     meta: array{title: string, description: string},
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

        /** @var FormattingExtension $formatting */
        $formatting = $container->get(FormattingExtension::class);
        $environment->addExtension($formatting);

        $environment->addGlobal('site', $fullConfig['site']);
        /** @var ViteManifest $assets */
        $assets = $container->get(ViteManifest::class);
        $environment->addFunction(new TwigFunction('vite_asset', $assets->asset(...)));
        /** @var CsrfToken $csrf */
        $csrf = $container->get(CsrfToken::class);
        $environment->addFunction(new TwigFunction('csrf_token', $csrf->generate(...)));

        return $environment;
    },

    'config' => [
        'twig' => [
            'debug'         => env_bool('APP_DEBUG', true),
            'template_dirs' => [
                FilesystemLoader::MAIN_NAMESPACE => __DIR__ . '/../../templates',
            ],
            'cache_dir' => __DIR__ . '/../../var/cache/twig',
        ],
    ],
];
