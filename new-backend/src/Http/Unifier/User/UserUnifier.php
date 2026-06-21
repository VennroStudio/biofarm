<?php

declare(strict_types=1);

namespace App\Http\Unifier\User;

use App\Components\Http\Unifier\UnifierInterface;
use App\Components\Storage\S3Transformer;
use App\Modules\User\ReadModel\User\Interface\UserModelInterface;
use Override;

final readonly class UserUnifier implements UnifierInterface
{
    public function __construct(
        private S3Transformer $s3Transformer,
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
     * @return list<array<string, bool|int|float|string|null|array<array-key, bool|int|float|string|null|array<array-key, bool|int|float|string|null>>>>
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
     * @return array<string, bool|int|float|string|null|array<array-key, bool|int|float|string|null|array<array-key, bool|int|float|string|null>>>
     */
    #[Override]
    public function map(object $item): array
    {
        /** @var UserModelInterface $item */
        $data = $item->toArray();

        $avatar = $data['avatar'] ?? null;
        $data['avatar'] = $this->s3Transformer->buildUrl($avatar);

        return $data;
    }
}
