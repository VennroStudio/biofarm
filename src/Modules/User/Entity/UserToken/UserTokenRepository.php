<?php

declare(strict_types=1);

namespace App\Modules\User\Entity\UserToken;

use App\Modules\User\Entity\UserToken\Fields\Enums\UserTokenType;

interface UserTokenRepository
{
    public function add(UserToken $token): void;

    public function getById(int $id): UserToken;

    public function findById(int $id): ?UserToken;

    /** @return UserToken[] */
    public function findByUserIdAndType(int $userId, UserTokenType $type): array;
}
