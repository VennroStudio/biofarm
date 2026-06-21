<?php

declare(strict_types=1);

namespace App\Modules\User\Command\UserToken\Create;

use App\Modules\User\Entity\UserToken\Fields\Enums\UserTokenType;
use DateTimeImmutable;

final readonly class CreateUserTokenCommand
{
    public function __construct(
        public int $userId,
        public UserTokenType $type,
        public string $tokenHash,
        public DateTimeImmutable $expiresAt,
    ) {}
}
