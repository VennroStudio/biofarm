<?php

declare(strict_types=1);

use App\Components\Http\Middleware\TranslatorLocale;
use Psr\Container\ContainerInterface;
use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;

use function App\Components\env;

return [
    TranslatorInterface::class => DI\get(Translator::class),

    Translator::class => static function (ContainerInterface $container): Translator {
        /** @var array{
         *     translator: array{
         *         lang: string,
         *         cache_dir: string|null,
         *         resources: array<string[]>
         *     },
         *     locales: array{allowed: array<string>}
         * } $fullConfig
         */
        $fullConfig = $container->get('config');
        $config = $fullConfig['translator'];
        $locales = $fullConfig['locales']['allowed'];

        $isDev = env('APP_ENV', 'prod') === 'dev';

        $translator = new Translator(
            locale: $config['lang'],
            formatter: null,
            cacheDir: $config['cache_dir'],
            debug: $isDev,
        );

        $translator->addLoader('php', new PhpFileLoader());
        $translator->addLoader('xlf', new XliffFileLoader());

        /** @var array<array{string, string, string, string}> $resources */
        $resources = $config['resources'];
        $modulesDir = __DIR__ . '/../../src/Modules';

        if (is_dir($modulesDir)) {
            foreach (new DirectoryIterator($modulesDir) as $entry) {
                if (!$entry->isDir() || $entry->isDot()) {
                    continue;
                }

                $module = $entry->getFilename();
                $moduleDir = $modulesDir . '/' . $module . '/Translation';

                if (!is_dir($moduleDir)) {
                    continue;
                }

                foreach ($locales as $locale) {
                    foreach (glob($moduleDir . '/*.' . $locale . '.php') ?: [] as $file) {
                        $filename = basename($file, '.' . $locale . '.php');
                        $moduleDomain = mb_strtolower($module);

                        $resources[] = ['php', $file, $locale, $filename];
                        $resources[] = ['php', $file, $locale, $moduleDomain];
                    }
                }
            }
        }

        $componentsTranslationsDir = __DIR__ . '/../../src/Components/Translation';
        if (is_dir($componentsTranslationsDir)) {
            foreach ($locales as $locale) {
                foreach (glob($componentsTranslationsDir . '/*.' . $locale . '.php') ?: [] as $file) {
                    $domain = basename($file, '.' . $locale . '.php');
                    $resources[] = ['php', $file, $locale, $domain];
                }
            }
        }

        foreach ($resources as $resource) {
            if (is_file($resource[1])) {
                $translator->addResource($resource[0], $resource[1], $resource[2], $resource[3]);
            }
        }

        return $translator;
    },

    TranslatorLocale::class => static function (ContainerInterface $container): TranslatorLocale {
        /** @var Translator $translator */
        $translator = $container->get(Translator::class);
        return new TranslatorLocale($translator);
    },

    'config' => [
        'translator' => [
            'lang'      => 'ru',
            'cache_dir' => env('APP_ENV', 'prod') !== 'dev'
                ? __DIR__ . '/../../var/cache/translator'
                : null,
            'resources' => [],
        ],
        'locales' => [
            'allowed' => ['en', 'ru'],
        ],
    ],
];
