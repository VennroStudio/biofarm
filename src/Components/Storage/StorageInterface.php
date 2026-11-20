<?php

declare(strict_types=1);

namespace App\Components\Storage;

use Psr\Http\Message\StreamInterface;

interface StorageInterface
{
    public function upload(string $path, StreamInterface|string $content, string $contentType): string;

    public function delete(string $path): void;

    public function url(string $path): string;
}
