<?php

declare(strict_types=1);

namespace App\Modules\User\Command\UserToken\Revoke;

use App\Modules\User\Entity\UserToken\Fields\Enums\UserTokenType;

final readonly class RevokeUserTokenCommand
{
    public function __construct(
        public string $tokenHash,
        public UserTokenType $type,
    ) {}
}
