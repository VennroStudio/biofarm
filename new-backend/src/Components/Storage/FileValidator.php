<?php

declare(strict_types=1);

namespace App\Components\Storage;

use App\Components\Exception\DomainExceptionModule;

readonly class FileValidator
{
    /**
     * @param array<string, string> $allowedMimeTypes mime => extension
     */
    public function __construct(
        private array $allowedMimeTypes,
        private int $maxFileSize,
    ) {}

    public function validate(string $contentType, int $fileSize): void
    {
        if (!\array_key_exists($contentType, $this->allowedMimeTypes)) {
            throw new DomainExceptionModule(
                module: 'components',
                message: 'error.invalid_mime_type',
                code: 14,
            );
        }

        if ($fileSize > $this->maxFileSize) {
            throw new DomainExceptionModule(
                module: 'components',
                message: 'error.file_too_large',
                code: 15,
            );
        }
    }

    public function getExtension(string $contentType): string
    {
        return $this->allowedMimeTypes[$contentType];
    }
}
