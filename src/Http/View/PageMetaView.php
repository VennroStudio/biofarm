<?php

declare(strict_types=1);

namespace App\Http\View;

final readonly class PageMetaView
{
    /**
     * @param list<array<string, mixed>> $jsonLd
     */
    public function __construct(
        public string $title,
        public string $description,
        public ?string $canonicalUrl = null,
        public string $robots = 'index, follow',
        public ?string $ogTitle = null,
        public ?string $ogDescription = null,
        public ?string $ogImage = null,
        public ?string $ogImageAlt = null,
        public string $ogType = 'website',
        public array $jsonLd = [],
    ) {}
}
