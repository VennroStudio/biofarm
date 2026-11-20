<?php

declare(strict_types=1);

namespace App\Http\View\Certificate;

use App\Http\View\Faq\FaqItemView;
use App\Http\View\PageMetaView;

final readonly class CertificatePageView
{
    /**
     * @param list<CertificateView> $certificates
     * @param list<FaqItemView> $faqItems
     */
    public function __construct(
        public PageMetaView $meta,
        public array $certificates,
        public array $faqItems,
    ) {}
}
