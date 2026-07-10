<?php

declare(strict_types=1);

namespace App\Http\Unifier\BlogPage;

use App\Http\View\Blog\BlogPageView;
use App\Http\View\PageMetaView;

final readonly class BlogPageUnifier
{
    public function unify(): BlogPageView
    {
        return new BlogPageView(
            meta: new PageMetaView(
                title: 'Блог — БИОФАРМ',
                description: 'Статьи и новости БИОФАРМ.',
            ),
            posts: [],
        );
    }
}
