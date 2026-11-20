<?php

declare(strict_types=1);

namespace App\Components\Http\Middleware\Identity;

use App\Components\Exception\AccessDeniedException;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class RequireAdmin implements MiddlewareInterface
{
    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $identity = RequestIdentity::get($request);

        if (!\in_array($identity->role, [UserRole::ADMIN, UserRole::DEVELOPER, UserRole::EDITOR], true)) {
            throw new AccessDeniedException();
        }

        return $handler->handle($request);
    }
}
