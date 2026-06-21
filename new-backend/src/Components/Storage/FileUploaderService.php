<?php

declare(strict_types=1);

namespace App\Components\Storage;

use finfo;
use Random\RandomException;
use RuntimeException;
use Slim\Psr7\Stream;

final readonly class FileUploaderService
{
    public function __construct(
        private StorageInterface $storage,
        private ?ImageCompressor $compressor = null,
    ) {}

    /**
     * @throws RandomException
     */
    public function upload(
        string $tmpFilePath,
        string $destinationDir,
        FileValidator $validator,
        ?string $oldFilePath = null,
    ): string {
        $mimeType = $this->detectMimeType($tmpFilePath);
        $fileSize = (int) filesize($tmpFilePath);

        $validator->validate($mimeType, $fileSize);

        $compressed = null;
        if ($this->compressor !== null && $validator instanceof ImageFileValidator) {
            $compressed  = $this->compressor->compress($tmpFilePath, $mimeType);
            $tmpFilePath = $compressed->path;
            $mimeType    = $compressed->mime;
        }

        try {
            if ($oldFilePath !== null && $oldFilePath !== '') {
                $this->storage->delete($oldFilePath);
            }

            $finalPath = $this->buildPath($destinationDir, $validator->getExtension($mimeType));

            $stream = $this->openStream($tmpFilePath);
            $this->storage->upload($finalPath, $stream, $mimeType);
            $stream->close();
        } finally {
            if ($compressed !== null) {
                @unlink($compressed->path);
            }
        }

        return $finalPath;
    }

    private function detectMimeType(string $filePath): string
    {
        $mimeType = new finfo(FILEINFO_MIME_TYPE)->file($filePath);

        if ($mimeType === false) {
            throw new RuntimeException("Cannot detect MIME type of: {$filePath}");
        }

        return $mimeType;
    }

    /**
     * @throws RandomException
     */
    private function buildPath(string $destinationDir, string $extension): string
    {
        $uuid = bin2hex(random_bytes(16));

        return sprintf('%s/%s.%s', rtrim($destinationDir, '/'), $uuid, $extension);
    }

    private function openStream(string $filePath): Stream
    {
        $resource = fopen($filePath, 'r+b');

        if ($resource === false) {
            throw new RuntimeException("Cannot open file: {$filePath}");
        }

        return new Stream($resource);
    }
}
