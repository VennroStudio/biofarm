<?php

declare(strict_types=1);

namespace App\Http\Unifier\Faq;

use App\Components\Seo\JsonLdFactory;
use App\Http\View\Faq\FaqPageView;
use App\Http\View\PageMetaView;
use App\Modules\Page\Service\PageSeoProvider;
use Doctrine\DBAL\Exception;

final readonly class FaqPageUnifier
{
    public function __construct(
        private FaqDataProvider $faq,
        private PageSeoProvider $seo,
        private JsonLdFactory $jsonLd,
    ) {}

    /**
     * @throws Exception
     */
    public function unify(): FaqPageView
    {
        $items = $this->faq->forPage('faq');
        $meta = $this->seo->systemMeta(
            'faq',
            '/faq',
            'Вопросы и ответы — БИОФАРМ',
            'Ответы на частые вопросы о продукции, заказах и бонусной программе БИОФАРМ.',
        );

        if ($items !== []) {
            $meta = $this->withJsonLd($meta, $this->jsonLd->faqPage($items));
        }

        return new FaqPageView($meta, $items);
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function withJsonLd(PageMetaView $meta, array $schema): PageMetaView
    {
        return new PageMetaView(
            title: $meta->title,
            description: $meta->description,
            canonicalUrl: $meta->canonicalUrl,
            robots: $meta->robots,
            ogTitle: $meta->ogTitle,
            ogDescription: $meta->ogDescription,
            ogImage: $meta->ogImage,
            ogImageAlt: $meta->ogImageAlt,
            ogType: $meta->ogType,
            jsonLd: [...$meta->jsonLd, $schema],
        );
    }
}
