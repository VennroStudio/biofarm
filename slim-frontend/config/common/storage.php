<?php

declare(strict_types=1);

use App\Components\Storage\FileUploaderService;
use App\Components\Storage\ImageCompressor;
use App\Components\Storage\ImageCompressorConfig;
use App\Components\Storage\S3Storage;
use App\Components\Storage\S3Transformer;
use App\Components\Storage\StorageInterface;
use Aws\S3\S3Client;
use Psr\Container\ContainerInterface;

use function App\Components\env;

return [
    S3Transformer::class => static fn (): S3Transformer => new S3Transformer(
        s3PublicUrl: rtrim(env('S3_PUBLIC_URL'), '/'),
    ),

    S3Client::class => static fn (): S3Client => new S3Client([
        'version'                 => 'latest',
        'region'                  => env('S3_REGION'),
        'endpoint'                => env('S3_ENDPOINT'),
        'use_path_style_endpoint' => true,
        'credentials'             => [
            'key'    => env('S3_KEY'),
            'secret' => env('S3_SECRET'),
        ],
    ]),

    StorageInterface::class => static function (ContainerInterface $container): StorageInterface {
        /** @var S3Client $client */
        $client = $container->get(S3Client::class);

        return new S3Storage(
            client: $client,
            bucket: env('S3_BUCKET'),
            publicUrl: env('S3_PUBLIC_URL'),
        );
    },

    ImageCompressor::class => static fn (): ImageCompressor => new ImageCompressor(
        quality:   ImageCompressorConfig::QUALITY,
        maxWidth:  ImageCompressorConfig::MAX_WIDTH,
        maxHeight: ImageCompressorConfig::MAX_HEIGHT,
    ),

    FileUploaderService::class => static function (ContainerInterface $container): FileUploaderService {
        return new FileUploaderService(
            storage:    $container->get(StorageInterface::class),
            compressor: $container->get(ImageCompressor::class),
        );
    },
];
