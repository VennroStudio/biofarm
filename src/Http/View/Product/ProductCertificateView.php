<?php

declare(strict_types=1);

namespace App\Http\View\Product;

final readonly class ProductCertificateView
{
    public function __construct(
        public int $id,
        public string $title,
        public string $filePath,
        public string $documentType,
        public ?string $description,
    ) {}
}
