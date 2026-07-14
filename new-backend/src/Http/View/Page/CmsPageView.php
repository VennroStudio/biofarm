<?php

declare(strict_types=1);

namespace App\Http\View\Page;

use App\Http\View\PageMetaView;
use App\Modules\Page\ReadModel\Page\PageDetails;

final readonly class CmsPageView
{
    public function __construct(
        public PageMetaView $meta,
        public ?PageDetails $page,
        public string $template,
    ) {}
}
