<?php

declare(strict_types=1);

namespace App\Http\Unifier\User;

use App\Components\Http\Unifier\UnifierInterface;
use App\Components\Storage\StorageInterface;
use App\Modules\User\ReadModel\User\Interface\UserModelInterface;
use Override;

final readonly class UserUnifier implements UnifierInterface
{
    public function __construct(
        private StorageInterface $storage,
    ) {}

    #[Override]
    public function unifyOne(?int $userId, ?object $item): array
    {
        if (!$item instanceof UserModelInterface) {
            return [];
        }

        return $this->unify($userId, [$item])[0] ?? [];
    }

    /**
     * @param list<object> $items
     * @return list<array<string, array<array-key, array<array-key, bool|float|int|string|null>|bool|float|int|string|null>|bool|float|int|string|null>>
     */
    #[Override]
    public function unify(?int $userId, array $items): array
    {
        if ($items === []) {
            return [];
        }

        return array_map($this->map(...), $items);
    }

    /**
     * @return array<string, array<array-key, array<array-key, bool|float|int|string|null>|bool|float|int|string|null>|bool|float|int|string|null>
     */
    #[Override]
    public function map(object $item): array
    {
        /** @var UserModelInterface $item */
        $data = $item->toArray();

        $avatar = $data['avatar'] ?? null;
        $data['avatar'] = \is_string($avatar) && $avatar !== '' ? $this->storage->url($avatar) : null;

        return $data;
    }
}
