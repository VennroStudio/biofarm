<?php

declare(strict_types=1);

namespace App\Http\View\Auth;

use App\Http\View\PageMetaView;

final readonly class EmailVerificationPageView
{
    public function __construct(
        public PageMetaView $meta,
        public string $status,
    ) {}
}
