<?php

declare(strict_types=1);

namespace App\Http\Unifier\Loyalty;

use App\Components\Seo\JsonLdFactory;
use App\Components\Setting\SiteSettings;
use App\Http\Unifier\Faq\FaqDataProvider;
use App\Http\View\Loyalty\LoyaltyPageView;
use App\Http\View\PageMetaView;
use App\Modules\Page\Service\PageSeoProvider;
use App\Modules\Program\Service\ProgramService;
use Doctrine\DBAL\Exception;

final readonly class LoyaltyPageUnifier
{
    public function __construct(
        private SiteSettings $settings,
        private FaqDataProvider $faq,
        private PageSeoProvider $seo,
        private JsonLdFactory $jsonLd,
        private ProgramService $program,
    ) {}

    /**
     * @throws Exception
     */
    public function unify(): LoyaltyPageView
    {
        $items = $this->faq->forPage('loyalty');
        $meta = $this->seo->systemMeta(
            'loyalty',
            '/loyalnost',
            'Бонусная программа — БИОФАРМ',
            'Бонусы, промокоды и реферальная программа БИОФАРМ для покупателей.',
        );

        if ($items !== []) {
            $meta = $this->withJsonLd($meta, $this->jsonLd->faqPage($items));
        }

        return new LoyaltyPageView(
            meta: $meta,
            cartEnabled: $this->settings->bool('cart_enabled'),
            orderBonusEnabled: $this->settings->bool('order_bonus_enabled'),
            orderBonusPercent: (float)$this->settings->get('order_bonus_percent'),
            orderBonusSpendLimitPercent: $this->settings->int('order_bonus_spend_limit_percent'),
            promoCodesEnabled: $this->settings->bool('promo_codes_enabled'),
            referralEnabled: $this->settings->bool('referral_enabled'),
            referralPercent: (float)$this->settings->get('referral_percent'),
            welcomeBonusEnabled: false,
            welcomeBonusAmount: $this->settings->int('welcome_bonus_amount'),
            freeDeliveryThreshold: $this->settings->int('free_delivery_threshold'),
            withdrawalsEnabled: $this->settings->bool('withdrawals_enabled'),
            faqItems: $items,
            programRules: $this->program->settings(),
        );
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
