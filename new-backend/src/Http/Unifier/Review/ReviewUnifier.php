<?php

declare(strict_types=1);

namespace App\Http\Unifier\Review;

use App\Components\Http\Unifier\UnifierInterface;
use App\Modules\Review\ReadModel\Review\Interface\ReviewModelInterface;
use Override;

final readonly class ReviewUnifier implements UnifierInterface
{
    #[Override]
    public function unifyOne(?int $userId, ?object $item): array
    {
        if (!$item instanceof ReviewModelInterface) {
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
        /** @var ReviewModelInterface $item */
        return $item->toArray();
    }
}
