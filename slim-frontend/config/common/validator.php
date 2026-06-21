<?php

declare(strict_types=1);

use App\Components\Validator\Validator;
use Psr\Container\ContainerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function App\Components\env;

return [
    ValidatorInterface::class => static function (ContainerInterface $container): ValidatorInterface {
        /** @var array{validator: array{cache_dir: string|null}} $fullConfig */
        $fullConfig = $container->get('config');
        $config = $fullConfig['validator'];

        /** @var TranslatorInterface $translator */
        $translator = $container->get(TranslatorInterface::class);

        $builder = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->setTranslator($translator);

        if ($config['cache_dir'] !== null) {
            $builder->setMappingCache(new FilesystemAdapter('', 0, $config['cache_dir']));
        }

        return $builder->getValidator();
    },

    Validator::class => DI\autowire(),

    'config' => [
        'validator' => [
            'cache_dir' => env('APP_ENV') !== 'dev'
                ? __DIR__ . '/../../var/cache/validator'
                : null,
        ],
    ],
];
