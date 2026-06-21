<?php

declare(strict_types=1);

namespace App\Components\Storage;

use GdImage;
use RuntimeException;

final readonly class ImageCompressor
{
    public function __construct(
        private int $quality,
        private int $maxWidth,
        private int $maxHeight,
    ) {}

    public function compress(string $sourcePath, string $sourceMime): CompressedImage
    {
        $image   = $this->load($sourcePath, $sourceMime);
        $image   = $this->resizeIfNeeded($image);
        $tmpPath = tempnam(sys_get_temp_dir(), 'img_') . '.avif';

        try {
            if (imageavif($image, $tmpPath, $this->quality) === false) {
                throw new RuntimeException("Cannot encode avif: {$tmpPath}");
            }
        } finally {
            imagedestroy($image);
        }

        return new CompressedImage(path: $tmpPath, mime: 'image/avif');
    }

    private function load(string $path, string $mime): GdImage
    {
        $image = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png'  => imagecreatefrompng($path),
            'image/webp' => imagecreatefromwebp($path),
            'image/gif'  => imagecreatefromgif($path),
            default      => throw new RuntimeException("Unsupported mime type: {$mime}"),
        };

        if ($image === false) {
            throw new RuntimeException("Cannot load image: {$path}");
        }

        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $image = $this->autoRotate($image, $path);
        }

        return $image;
    }

    private function resizeIfNeeded(GdImage $image): GdImage
    {
        $w = imagesx($image);
        $h = imagesy($image);

        if ($w <= $this->maxWidth && $h <= $this->maxHeight) {
            return $image;
        }

        $ratio   = min($this->maxWidth / $w, $this->maxHeight / $h);
        $newW    = (int) round($w * $ratio);
        $newH    = (int) round($h * $ratio);
        $resized = imagecreatetruecolor($newW, $newH);

        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newW, $newH, $w, $h);
        imagedestroy($image);

        return $resized;
    }

    private function autoRotate(GdImage $image, string $path): GdImage
    {
        $exif        = @exif_read_data($path);
        $orientation = $exif['Orientation'] ?? 1;

        $rotated = match ($orientation) {
            3       => imagerotate($image, 180, 0),
            6       => imagerotate($image, -90, 0),
            8       => imagerotate($image, 90, 0),
            default => null,
        };

        if ($rotated === null) {
            return $image;
        }

        imagedestroy($image);

        return $rotated;
    }
}
