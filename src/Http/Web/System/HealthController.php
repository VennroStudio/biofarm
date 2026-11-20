<?php

declare(strict_types=1);

namespace App\Http\Web\System;

use App\Components\Http\Response\JsonResponse;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class HealthController implements RequestHandlerInterface
{
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        unset($request);

        return new JsonResponse([
            'status'  => 'ok',
            'service' => 'biofarm',
        ]);
    }
}
