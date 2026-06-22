<?php

declare(strict_types=1);

namespace App\Components\Asset;

use JsonException;
use RuntimeException;

final class ViteManifest
{
    /** @var array<string, array{file?: string}>|null */
    private ?array $manifest = null;

    public function __construct(
        private readonly string $manifestPath,
        private readonly string $buildBasePath,
    ) {}

    public function asset(string $entry): string
    {
        $item = $this->manifest()[$entry] ?? null;
        if ($item === null) {
            throw new RuntimeException("Vite manifest entry '{$entry}' not found.");
        }

        $file = $item['file'] ?? null;
        if ($file === null || $file === '') {
            throw new RuntimeException("Vite manifest entry '{$entry}' has no output file.");
        }

        return rtrim($this->buildBasePath, '/') . '/' . ltrim($file, '/');
    }

    /**
     * @return array<string, array{file?: string}>
     */
    private function manifest(): array
    {
        if ($this->manifest !== null) {
            return $this->manifest;
        }

        if (!is_file($this->manifestPath)) {
            throw new RuntimeException('Vite manifest not found. Run npm run build.');
        }

        try {
            $decoded = json_decode((string)file_get_contents($this->manifestPath), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Vite manifest is not valid JSON.', 0, $exception);
        }

        if (!\is_array($decoded)) {
            throw new RuntimeException('Vite manifest must be a JSON object.');
        }

        /** @var array<string, array{file?: string}> $manifest */
        $manifest = $decoded;
        $this->manifest = $manifest;

        return $manifest;
    }
}
