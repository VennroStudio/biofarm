<?php

declare(strict_types=1);

namespace App\Components\Seo;

use function App\Components\env;

final readonly class SeoUrlGenerator
{
    public function absolute(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return $this->baseUrl() . '/' . ltrim($path, '/');
    }

    public function baseUrl(): string
    {
        return rtrim(env('APP_URL', 'http://localhost'), '/');
    }
}
