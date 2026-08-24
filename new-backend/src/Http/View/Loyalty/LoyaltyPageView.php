<?php

declare(strict_types=1);

namespace App\Http\View\Loyalty;

use App\Http\View\Faq\FaqItemView;
use App\Http\View\PageMetaView;

final readonly class LoyaltyPageView
{
    /**
     * @param list<FaqItemView> $faqItems
     */
    public function __construct(
        public PageMetaView $meta,
        public bool $cartEnabled,
        public bool $orderBonusEnabled,
        public int $orderBonusPercent,
        public int $orderBonusSpendLimitPercent,
        public bool $promoCodesEnabled,
        public bool $referralEnabled,
        public int $referralPercent,
        public bool $welcomeBonusEnabled,
        public int $welcomeBonusAmount,
        public int $freeDeliveryThreshold,
        public bool $withdrawalsEnabled,
        public array $faqItems,
    ) {}
}
