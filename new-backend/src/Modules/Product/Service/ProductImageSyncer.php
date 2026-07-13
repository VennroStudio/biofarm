<?php

declare(strict_types=1);

namespace App\Modules\Product\Service;

use Doctrine\DBAL\Connection;

final readonly class ProductImageSyncer
{
    private string $publicDir;

    public function __construct(
        private Connection $connection,
    ) {
        $this->publicDir = dirname(__DIR__, 4) . '/public';
    }

    /**
     * @param list<string>|null $images
     * @param list<array{path?: string, alt?: string|null, title?: string|null, sortOrder?: int|null, sort_order?: int|null, isMain?: bool|null, is_main?: bool|null}>|null $productImages
     */
    public function sync(
        int $productId,
        string $mainImage,
        ?string $alt,
        string $title,
        ?array $images,
        ?array $productImages = null,
    ): void
    {
        $items = $this->items($mainImage, $alt, $title, $images, $productImages);

        $this->connection->delete('product_images', ['product_id' => $productId]);

        foreach ($items as $index => $item) {
            $metadata = $this->metadata($item['path']);

            $this->connection->insert('product_images', [
                'product_id' => $productId,
                'path' => $item['path'],
                'alt' => $item['alt'] ?: ($item['is_main'] ? ($alt ?: $title) : $title),
                'title' => $item['title'] ?: $title,
                'sort_order' => $index,
                'is_main' => $item['is_main'] ? 1 : 0,
                'width' => $metadata['width'],
                'height' => $metadata['height'],
                'mime_type' => $metadata['mime_type'],
                'size' => $metadata['size'],
                'created_at' => gmdate('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * @param list<string>|null $images
     * @param list<array{path?: string, alt?: string|null, title?: string|null, sortOrder?: int|null, sort_order?: int|null, isMain?: bool|null, is_main?: bool|null}>|null $productImages
     * @return list<array{path: string, alt: string|null, title: string|null, sort_order: int, is_main: bool}>
     */
    private function items(string $mainImage, ?string $alt, string $title, ?array $images, ?array $productImages): array
    {
        $items = [];

        if ($productImages !== null && $productImages !== []) {
            foreach ($productImages as $index => $image) {
                if (!\is_array($image)) {
                    continue;
                }

                $path = trim((string)($image['path'] ?? ''));
                if ($path === '' || isset($items[$path])) {
                    continue;
                }

                $items[$path] = [
                    'path' => $path,
                    'alt' => $this->nullableText($image['alt'] ?? null),
                    'title' => $this->nullableText($image['title'] ?? null),
                    'sort_order' => (int)($image['sort_order'] ?? $image['sortOrder'] ?? $index),
                    'is_main' => (bool)($image['is_main'] ?? $image['isMain'] ?? false),
                ];
            }
        }

        if ($items === []) {
            foreach ($this->paths($mainImage, $images) as $index => $path) {
                $items[$path] = [
                    'path' => $path,
                    'alt' => $index === 0 ? ($alt ?: $title) : $title,
                    'title' => $title,
                    'sort_order' => $index,
                    'is_main' => $index === 0,
                ];
            }
        }

        if ($items === []) {
            return [];
        }

        uasort($items, static fn (array $left, array $right): int => $left['sort_order'] <=> $right['sort_order']);
        $items = array_values($items);

        $mainIndex = null;
        foreach ($items as $index => $item) {
            if ($item['is_main']) {
                $mainIndex = $index;
                break;
            }
        }

        $mainIndex ??= 0;
        foreach ($items as $index => $item) {
            $items[$index]['is_main'] = $index === $mainIndex;
        }

        return $items;
    }

    /**
     * @param list<string>|null $images
     * @return list<string>
     */
    private function paths(string $mainImage, ?array $images): array
    {
        $paths = [];

        foreach ([$mainImage, ...($images ?? [])] as $path) {
            $path = trim((string)$path);

            if ($path === '' || isset($paths[$path])) {
                continue;
            }

            $paths[$path] = $path;
        }

        return array_values($paths);
    }

    /**
     * @return array{width: int|null, height: int|null, mime_type: string|null, size: int|null}
     */
    private function metadata(string $path): array
    {
        $filePath = $this->localFilePath($path);
        if ($filePath === null || !is_file($filePath)) {
            return [
                'width' => null,
                'height' => null,
                'mime_type' => null,
                'size' => null,
            ];
        }

        $size = @getimagesize($filePath);

        return [
            'width' => \is_array($size) ? (int)$size[0] : null,
            'height' => \is_array($size) ? (int)$size[1] : null,
            'mime_type' => \is_array($size) && isset($size['mime']) ? (string)$size['mime'] : null,
            'size' => (int)filesize($filePath),
        ];
    }

    private function localFilePath(string $path): ?string
    {
        $path = trim($path);
        if ($path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return null;
        }

        return $this->publicDir . '/' . ltrim($path, '/');
    }

    private function nullableText(mixed $value): ?string
    {
        if (!\is_scalar($value)) {
            return null;
        }

        $value = trim((string)$value);

        return $value === '' ? null : $value;
    }
}
