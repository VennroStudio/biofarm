<?php

declare(strict_types=1);

namespace App\Http\View\Faq;

use App\Http\View\PageMetaView;

final readonly class FaqPageView
{
    /**
     * @param list<FaqItemView> $items
     */
    public function __construct(
        public PageMetaView $meta,
        public array $items,
    ) {}
}
