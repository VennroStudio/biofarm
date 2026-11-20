<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Auth;

use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataResponse;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class MeAction implements RequestHandlerInterface
{
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $identity = RequestIdentity::get($request);

        return new JsonDataResponse([
            'id'         => $identity->id,
            'first_name' => $identity->firstName,
            'role'       => [
                'id'    => $identity->role->value,
                'label' => $identity->role->getLabel(),
            ],
        ]);
    }
}
