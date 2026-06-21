<?php

declare(strict_types=1);

namespace App\Modules\User\Command\Auth\Logout;

use App\Modules\User\Command\UserToken\Revoke\RevokeUserTokenCommand;
use App\Modules\User\Command\UserToken\Revoke\RevokeUserTokenHandler;
use App\Modules\User\Entity\UserToken\Fields\Enums\UserTokenType;
use App\Modules\User\Service\TokenHasherService;

final readonly class LogoutHandler
{
    public function __construct(
        private RevokeUserTokenHandler $revokeUserTokenHandler,
        private TokenHasherService $tokenHasher,
    ) {}

    public function handle(LogoutCommand $command): void
    {
        $this->revokeUserTokenHandler->handle(new RevokeUserTokenCommand(
            tokenHash: $this->tokenHasher->hash($command->refreshToken),
            type: UserTokenType::REFRESH,
        ));
    }
}
