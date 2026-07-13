<?php

declare(strict_types=1);

use App\Console\HelloCommand;
use App\Console\GenerateImageVariantsCommand;
use App\Console\LocalizeBlogImagesCommand;
use App\Console\LocalizeProductImagesCommand;
use App\Console\LocalizeReviewImagesCommand;
use App\Components\Image\ImageVariantGenerator;
use Psr\Container\ContainerInterface;

return [
    GenerateImageVariantsCommand::class => static fn (ContainerInterface $container): GenerateImageVariantsCommand => new GenerateImageVariantsCommand(
        generator: (static function (mixed $generator): ImageVariantGenerator {
            assert($generator instanceof ImageVariantGenerator);

            return $generator;
        })($container->get(ImageVariantGenerator::class)),
        uploadsPath: (string)$container->get('image.uploads_path'),
        publicRoot: (string)$container->get('image.public_root'),
    ),

    'config' => [
        'console' => [
            'commands' => [
                HelloCommand::class,
                GenerateImageVariantsCommand::class,
                LocalizeBlogImagesCommand::class,
                LocalizeProductImagesCommand::class,
                LocalizeReviewImagesCommand::class,
            ],
        ],
    ],
];
