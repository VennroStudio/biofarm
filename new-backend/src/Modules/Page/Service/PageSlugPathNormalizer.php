<?php

declare(strict_types=1);

namespace App\Modules\Page\Service;

use App\Components\Exception\DomainExceptionModule;
use App\Components\String\SlugGenerator;

final readonly class PageSlugPathNormalizer
{
    private const array RESERVED_FIRST_SEGMENTS = [
        'admin',
        'api',
        'v1',
        'assets',
        'uploads',
        'catalog',
        'product',
        'blog',
        'cart',
        'checkout',
        'order-success',
        'login',
        'profile',
        'privacy',
        'oferta',
        'robots.txt',
        'sitemap.xml',
        'healthz',
        'readyz',
    ];

    public function __construct(
        private SlugGenerator $slugGenerator,
    ) {}

    public function normalize(string $value): string
    {
        $value = trim($value);
        $value = trim($value, '/');

        if ($value === '') {
            throw new DomainExceptionModule('page', 'error.page_slug_required', 6);
        }

        $rawSegments = preg_split('#/+#', $value) ?: [];
        $segments = [];

        foreach ($rawSegments as $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }

            $segments[] = $this->slugGenerator->generate($segment);
        }

        if ($segments === []) {
            throw new DomainExceptionModule('page', 'error.page_slug_required', 6);
        }

        if (\in_array($segments[0], self::RESERVED_FIRST_SEGMENTS, true)) {
            throw new DomainExceptionModule('page', 'error.page_slug_reserved', 7);
        }

        return implode('/', $segments);
    }
}
