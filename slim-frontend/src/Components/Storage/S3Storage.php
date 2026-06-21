<?php

declare(strict_types=1);

namespace App\Components\Storage;

use Aws\S3\S3Client;
use Override;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Throwable;

final readonly class S3Storage implements StorageInterface
{
    public function __construct(
        private S3Client $client,
        private string $bucket,
        private string $publicUrl,
    ) {}

    #[Override]
    public function upload(string $path, StreamInterface|string $content, string $contentType): string
    {
        try {
            $this->client->putObject([
                'Bucket'      => $this->bucket,
                'Key'         => $path,
                'Body'        => $content,
                'ContentType' => $contentType,
            ]);
        } catch (Throwable $e) {
            throw new RuntimeException("Failed to upload file '{$path}': " . $e->getMessage(), 0, $e);
        }

        return $this->url($path);
    }

    #[Override]
    public function delete(string $path): void
    {
        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key'    => $path,
            ]);
        } catch (Throwable $e) {
            throw new RuntimeException("Failed to delete file '{$path}': " . $e->getMessage(), 0, $e);
        }
    }

    #[Override]
    public function url(string $path): string
    {
        return rtrim($this->publicUrl, '/') . '/' . ltrim($path, '/');
    }
}
