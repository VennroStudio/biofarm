<?php

declare(strict_types=1);

namespace App\Modules\User\Query\UserToken\FindByHash;

use App\Modules\User\Entity\UserToken\Fields\Enums\UserTokenType;

final readonly class UserTokenFindByHashQuery
{
    public function __construct(
        public string $tokenHash,
        public UserTokenType $type,
    ) {}
}
