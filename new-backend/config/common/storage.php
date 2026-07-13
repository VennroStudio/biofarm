<?php

declare(strict_types=1);

use App\Components\Storage\FileUploaderService;
use App\Components\Storage\ImageCompressor;
use App\Components\Storage\ImageCompressorConfig;
use App\Components\Storage\LocalStorage;
use App\Components\Storage\StorageInterface;
use App\Components\Image\ImageVariantGenerator;
use App\Components\Image\ImageVariantLocator;
use Psr\Container\ContainerInterface;

use function App\Components\env;

return [
    StorageInterface::class => static fn (): StorageInterface => new LocalStorage(
        storagePath: env('UPLOADS_STORAGE_PATH', __DIR__ . '/../../public/uploads'),
        publicPath: env('UPLOADS_PUBLIC_PATH', '/uploads'),
    ),

    ImageCompressor::class => static fn (): ImageCompressor => new ImageCompressor(
        quality: ImageCompressorConfig::QUALITY,
        maxWidth: ImageCompressorConfig::MAX_WIDTH,
        maxHeight: ImageCompressorConfig::MAX_HEIGHT,
    ),

    FileUploaderService::class => static fn (ContainerInterface $container): FileUploaderService => new FileUploaderService(
        storage: (static function (mixed $storage): StorageInterface {
            assert($storage instanceof StorageInterface);

            return $storage;
        })($container->get(StorageInterface::class)),
        compressor: (static function (mixed $compressor): ImageCompressor {
            assert($compressor instanceof ImageCompressor);

            return $compressor;
        })($container->get(ImageCompressor::class)),
    ),

    ImageVariantLocator::class => static fn (): ImageVariantLocator => new ImageVariantLocator(
        publicRoot: \dirname(env('UPLOADS_STORAGE_PATH', __DIR__ . '/../../public/uploads')),
    ),

    ImageVariantGenerator::class => static fn (ContainerInterface $container): ImageVariantGenerator => new ImageVariantGenerator(
        locator: (static function (mixed $locator): ImageVariantLocator {
            assert($locator instanceof ImageVariantLocator);

            return $locator;
        })($container->get(ImageVariantLocator::class)),
    ),

    'image.uploads_path' => static fn (): string => env('UPLOADS_STORAGE_PATH', __DIR__ . '/../../public/uploads'),
    'image.public_root' => static fn (): string => \dirname(env('UPLOADS_STORAGE_PATH', __DIR__ . '/../../public/uploads')),
];
