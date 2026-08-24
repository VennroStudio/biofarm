<?php

declare(strict_types=1);

namespace App\Components\Storage;

use Override;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

final readonly class LocalStorage implements StorageInterface
{
    public function __construct(
        private string $storagePath,
        private string $publicPath,
    ) {}

    #[Override]
    public function upload(string $path, StreamInterface|string $content, string $contentType): string
    {
        $targetPath = $this->absolutePath($path);
        $directory = \dirname($targetPath);

        if (!is_dir($directory) && !mkdir($directory, 0o775, true) && !is_dir($directory)) {
            throw new RuntimeException("Cannot create upload directory: {$directory}");
        }

        if (\is_string($content)) {
            if (file_put_contents($targetPath, $content) === false) {
                throw new RuntimeException("Cannot write uploaded file: {$targetPath}");
            }

            return $this->url($path);
        }

        $this->writeStream($targetPath, $content);

        return $this->url($path);
    }

    #[Override]
    public function delete(string $path): void
    {
        $targetPath = $this->absolutePath($path);

        if (is_file($targetPath) && !unlink($targetPath)) {
            throw new RuntimeException("Cannot delete uploaded file: {$targetPath}");
        }
    }

    #[Override]
    public function url(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return rtrim($this->publicPath, '/') . '/' . ltrim($path, '/');
    }

    public function absolutePath(string $path): string
    {
        return rtrim($this->storagePath, '/') . '/' . ltrim($path, '/');
    }

    private function writeStream(string $targetPath, StreamInterface $stream): void
    {
        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        $target = fopen($targetPath, 'wb');
        if ($target === false) {
            throw new RuntimeException("Cannot open uploaded file target: {$targetPath}");
        }

        try {
            while (!$stream->eof()) {
                $chunk = $stream->read(8192);
                if ($chunk === '') {
                    break;
                }

                if (fwrite($target, $chunk) === false) {
                    throw new RuntimeException("Cannot write uploaded file: {$targetPath}");
                }
            }
        } finally {
            fclose($target);
        }
    }
}
