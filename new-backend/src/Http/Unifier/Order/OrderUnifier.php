<?php

declare(strict_types=1);

namespace App\Http\Unifier\Order;

use App\Components\Http\Unifier\UnifierInterface;
use App\Modules\Order\ReadModel\Order\Interface\OrderModelInterface;
use Override;

final readonly class OrderUnifier implements UnifierInterface
{
    #[Override]
    public function unifyOne(?int $userId, ?object $item): array
    {
        if (!$item instanceof OrderModelInterface) {
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
        /** @var OrderModelInterface $item */
        return $item->toArray();
    }
}
