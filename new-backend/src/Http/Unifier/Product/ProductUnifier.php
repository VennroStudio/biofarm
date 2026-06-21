<?php

declare(strict_types=1);

namespace App\Http\Unifier\Product;

use App\Components\Http\Unifier\UnifierInterface;
use App\Modules\Product\ReadModel\Product\Interface\ProductModelInterface;
use Override;

final readonly class ProductUnifier implements UnifierInterface
{
    #[Override]
    public function unifyOne(?int $userId, ?object $item): array
    {
        if (!$item instanceof ProductModelInterface) {
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
        /** @var ProductModelInterface $item */
        return $item->toArray();
    }
}
