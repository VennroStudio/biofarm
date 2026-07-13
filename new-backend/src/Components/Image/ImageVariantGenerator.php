<?php

declare(strict_types=1);

namespace App\Components\Image;

use GdImage;
use RuntimeException;

final readonly class ImageVariantGenerator
{
    /** @var array<string, string> */
    private const array SUPPORTED_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/avif' => 'avif',
    ];

    public function __construct(
        private ImageVariantLocator $locator,
        private int $webpQuality = 84,
        private int $avifQuality = 62,
    ) {}

    /**
     * @return list<string>
     */
    public function generate(string $src, bool $overwrite = false): array
    {
        $sourcePath = $this->locator->absolutePath($src);
        if ($sourcePath === null || !is_file($sourcePath)) {
            return [];
        }

        $mimeType = $this->mimeType($sourcePath);
        if (!isset(self::SUPPORTED_MIMES[$mimeType])) {
            return [];
        }

        $sourceExtension = strtolower((string)pathinfo($sourcePath, PATHINFO_EXTENSION));
        $targetBase = $this->withoutExtension($sourcePath);
        $created = [];

        foreach (['webp', 'avif'] as $targetExtension) {
            if ($sourceExtension === $targetExtension) {
                continue;
            }

            $targetPath = $targetBase . '.' . $targetExtension;
            if (!$overwrite && is_file($targetPath)) {
                continue;
            }

            $image = $this->load($sourcePath, $mimeType);
            $this->prepareAlpha($image);

            try {
                $this->encode($image, $targetPath, $targetExtension);
                $created[] = $targetPath;
            } finally {
                imagedestroy($image);
            }
        }

        return $created;
    }

    private function mimeType(string $path): string
    {
        $mimeType = @mime_content_type($path);
        if (!\is_string($mimeType)) {
            throw new RuntimeException("Cannot detect image MIME type: {$path}");
        }

        return $mimeType;
    }

    private function load(string $path, string $mimeType): GdImage
    {
        $image = match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png'  => imagecreatefrompng($path),
            'image/webp' => imagecreatefromwebp($path),
            'image/avif' => imagecreatefromavif($path),
            default      => false,
        };

        if (!$image instanceof GdImage) {
            throw new RuntimeException("Cannot read image: {$path}");
        }

        return $image;
    }

    private function prepareAlpha(GdImage $image): void
    {
        if (!imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        imagealphablending($image, true);
        imagesavealpha($image, true);
    }

    private function encode(GdImage $image, string $targetPath, string $targetExtension): void
    {
        $directory = \dirname($targetPath);
        if (!is_dir($directory) && !mkdir($directory, 0o775, true) && !is_dir($directory)) {
            throw new RuntimeException("Cannot create image variant directory: {$directory}");
        }

        $success = match ($targetExtension) {
            'webp'  => imagewebp($image, $targetPath, $this->webpQuality),
            'avif'  => imageavif($image, $targetPath, $this->avifQuality),
            default => false,
        };

        if (!$success || !is_file($targetPath) || filesize($targetPath) === 0) {
            @unlink($targetPath);
            throw new RuntimeException("Cannot write image variant: {$targetPath}");
        }
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
