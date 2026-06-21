<?php

declare(strict_types=1);

use App\Components\Storage\ImageFileValidator;
use App\Components\Storage\VideoFileValidator;

return [
    ImageFileValidator::class => static fn (): ImageFileValidator => new ImageFileValidator(
        allowedMimeTypes: [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            'image/avif' => 'avif',
        ],
        maxFileSize: 2 * 1024 * 1024,
    ),

    VideoFileValidator::class => static fn (): VideoFileValidator => new VideoFileValidator(
        allowedMimeTypes: [
            'video/mp4'       => 'mp4',
            'video/webm'      => 'webm',
            'video/quicktime' => 'mov',
        ],
        maxFileSize: 500 * 1024 * 1024,
    ),
];
