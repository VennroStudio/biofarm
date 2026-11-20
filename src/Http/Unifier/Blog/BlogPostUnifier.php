<?php

declare(strict_types=1);

namespace App\Http\Unifier\Blog;

use App\Components\Http\Unifier\UnifierInterface;
use App\Modules\Blog\ReadModel\BlogPost\Interface\BlogPostModelInterface;
use Override;

final readonly class BlogPostUnifier implements UnifierInterface
{
    #[Override]
    public function unifyOne(?int $userId, ?object $item): array
    {
        if (!$item instanceof BlogPostModelInterface) {
            return [];
        }

        return $this->unify($userId, [$item])[0] ?? [];
    }

    /**
     * @param list<object> $items
     */
    #[Override]
    public function unify(?int $userId, array $items): array
    {
        return array_map($this->map(...), $items);
    }

    #[Override]
    public function map(object $item): array
    {
        /** @var BlogPostModelInterface $item */
        return $item->toArray();
    }
}
