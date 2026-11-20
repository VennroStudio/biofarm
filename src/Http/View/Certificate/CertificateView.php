<?php

declare(strict_types=1);

namespace App\Http\View\Certificate;

final readonly class CertificateView
{
    public function __construct(
        public int $id,
        public string $title,
        public string $filePath,
        public string $documentType,
        public ?int $productId,
        public ?string $productName,
        public ?string $productSlug,
        public ?string $description,
    ) {}
}
