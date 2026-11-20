<?php

declare(strict_types=1);

namespace App\Http\Unifier\Page;

use App\Components\Seo\JsonLdFactory;
use App\Http\Unifier\Certificate\CertificateDataProvider;
use App\Http\Unifier\Faq\FaqDataProvider;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/** Additional material sections for CMS pages that have no dedicated sections. */
final class PageMaterialsTwigExtension extends AbstractExtension
{
    public function __construct(
        private readonly CertificateDataProvider $certificates,
        private readonly FaqDataProvider $faq,
        private readonly JsonLdFactory $jsonLd,
    ) {}

    #[Override]
    public function getFunctions(): array
    {
        return [new TwigFunction('page_material_sections', $this->sections(...))];
    }

    public function sections(?string $canonicalUrl, bool $includeCertificates, bool $includeFaq): array
    {
        $path = $canonicalUrl !== null ? parse_url($canonicalUrl, PHP_URL_PATH) : null;
        if (!\is_string($path)) {
            return ['certificates' => [], 'faq' => [], 'jsonLd' => null];
        }
        $certificates = $includeCertificates ? $this->certificates->forPage('page', $path) : [];
        $faq = $includeFaq ? $this->faq->forPage('page', $path) : [];

        return [
            'certificates' => $certificates,
            'faq' => $faq,
            'jsonLd' => $faq !== [] ? $this->jsonLd->faqPage($faq) : null,
        ];
    }
}
