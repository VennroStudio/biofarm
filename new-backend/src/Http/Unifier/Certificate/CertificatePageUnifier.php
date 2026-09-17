<?php

declare(strict_types=1);

namespace App\Http\Unifier\Certificate;

use App\Components\Seo\JsonLdFactory;
use App\Components\Seo\SeoUrlGenerator;
use App\Http\Unifier\Faq\FaqDataProvider;
use App\Http\View\Certificate\CertificatePageView;
use App\Http\View\Certificate\CertificateView;
use App\Http\View\PageMetaView;
use App\Modules\Page\Service\PageSeoProvider;
use Doctrine\DBAL\Exception;

final readonly class CertificatePageUnifier
{
    public function __construct(
        private CertificateDataProvider $certificateData,
        private SeoUrlGenerator $urls,
        private JsonLdFactory $jsonLd,
        private PageSeoProvider $pages,
        private FaqDataProvider $faq,
    ) {}

    /**
     * @throws Exception
     */
    public function unify(): CertificatePageView
    {
        $certificates = $this->certificates();
        $faqItems = $this->faq->forPage('certificates');
        $title = 'Сертификаты качества — БИОФАРМ';
        $description = 'Сертификаты и документы на натуральную продукцию БИОФАРМ.';

        $jsonLd = [
            $this->jsonLd->breadcrumbs([
                ['name' => 'Главная', 'url' => '/'],
                ['name' => 'Сертификаты', 'url' => '/certificates'],
            ]),
            $this->jsonLd->webPage('Сертификаты качества', $description, '/certificates'),
        ];

        if ($faqItems !== []) {
            $jsonLd[] = $this->jsonLd->faqPage($faqItems);
        }

        return new CertificatePageView(
            meta: $this->pages->applySystem('certificates', new PageMetaView(
                title: $title,
                description: $description,
                canonicalUrl: $this->urls->absolute('/certificates'),
                robots: 'index, follow',
                ogTitle: $title,
                ogDescription: $description,
                ogImage: null,
                ogImageAlt: 'Сертификаты БИОФАРМ',
                jsonLd: $jsonLd,
            )),
            certificates: $certificates,
            faqItems: $faqItems,
        );
    }

    /**
     * @return list<CertificateView>
     * @throws Exception
     */
    private function certificates(): array
    {
        return $this->certificateData->forPage('certificates');
    }
}
