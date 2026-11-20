<?php

declare(strict_types=1);

namespace App\Components\Image;

final readonly class ImageVariantLocator
{
    /** @var array<string, string> */
    private const array MIME_TYPES = [
        'avif' => 'image/avif',
        'webp' => 'image/webp',
    ];

    public function __construct(
        private string $publicRoot,
    ) {}

    /**
     * @return list<array{src: string, type: string}>
     */
    public function sources(string $src): array
    {
        $path = $this->localPublicPath($src);
        if ($path === null) {
            return [];
        }

        $extension = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));
        $base = $this->withoutExtension($path);
        $sources = [];

        foreach (self::MIME_TYPES as $targetExtension => $mimeType) {
            $candidate = $extension === $targetExtension ? $path : $base . '.' . $targetExtension;
            if ($this->exists($candidate)) {
                $sources[] = [
                    'src'  => $candidate,
                    'type' => $mimeType,
                ];
            }
        }

        return $sources;
    }

    public function absolutePath(string $src): ?string
    {
        $path = $this->localPublicPath($src);
        if ($path === null) {
            return null;
        }

        return $this->absolutePublicPath($path);
    }

    private function localPublicPath(string $src): ?string
    {
        $path = parse_url($src, PHP_URL_PATH);
        if (!\is_string($path) || $path === '') {
            return null;
        }

        if (!str_starts_with($path, '/uploads/')) {
            return null;
        }

        if (str_contains($path, '..')) {
            return null;
        }

        return $path;
    }

    private function exists(string $publicPath): bool
    {
        return is_file($this->absolutePublicPath($publicPath));
    }

    private function absolutePublicPath(string $publicPath): string
    {
        return rtrim($this->publicRoot, '/') . '/' . ltrim($publicPath, '/');
    }

    private function withoutExtension(string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if ($extension === '') {
            return $path;
        }

        return substr($path, 0, -\strlen($extension) - 1);
    }
}
