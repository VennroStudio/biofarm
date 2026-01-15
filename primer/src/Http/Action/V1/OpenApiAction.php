<?php

declare(strict_types=1);

namespace App\Http\Action\V1;

use OpenApi\Attributes as OA;
use OpenApi\Generator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use ZayMedia\Shared\Helpers\OpenApi\SecuritySchemeApiKeyAuth;
use ZayMedia\Shared\Http\Response\JsonResponse;

#[OA\Info(
    version: '1.0',
    title: 'API',
)]
#[SecuritySchemeApiKeyAuth]
#[OA\Server(
    url: '/v1/'
)]
final class OpenApiAction implements RequestHandlerInterface
{
    public function handle(Request $request): Response
    {
        $openapi = Generator::scan(['/app/src/Http/Action/V1']);

        return new JsonResponse($openapi);
    }
}
