<?php

declare(strict_types=1);

namespace App\Http\Unifier\BlogPage;

use App\Http\View\Blog\BlogPostPageView;
use App\Http\View\PageMetaView;

final readonly class BlogPostPageUnifier
{
    public function unify(string $slug): BlogPostPageView
    {
        unset($slug);

        return new BlogPostPageView(
            meta: new PageMetaView(
                title: 'Статья — БИОФАРМ',
                description: 'Материал блога БИОФАРМ.',
            ),
            post: null,
        );
    }
}
