<?php

declare(strict_types=1);

namespace App\Http\Action\V1\Spotify;

use App\Modules\Command\SpotifyToken\Refresh\Command;
use App\Modules\Command\SpotifyToken\Refresh\Handler;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ZayMedia\Shared\Components\Router\Route;
use ZayMedia\Shared\Components\Serializer\Denormalizer;
use ZayMedia\Shared\Components\Validator\Validator;
use ZayMedia\Shared\Helpers\OpenApi\ResponseSuccessful;
use ZayMedia\Shared\Helpers\OpenApi\Security;
use ZayMedia\Shared\Http\Middleware\AuthenticateByKey;
use ZayMedia\Shared\Http\Response\JsonDataSuccessResponse;

#[OA\Put(
    path: '/spotify-tokens/{id}',
    description: 'Обновление токена',
    summary: 'Обновление токена',
    security: [Security::BEARER_AUTH],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'accessToken',
                    type: 'string',
                    example: 'token'
                ),
            ]
        )
    ),
    tags: ['Spotify'],
    responses: [new ResponseSuccessful()]
)]
#[OA\Parameter(
    name: 'id',
    description: 'Идентификатор токена',
    in: 'path',
    required: true,
    schema: new OA\Schema(
        type: 'integer',
        format: 'int64'
    ),
    example: 1
)]
final readonly class RefreshTokenAction implements RequestHandlerInterface
{
    public function __construct(
        private Denormalizer $denormalizer,
        private Handler $handler,
        private Validator $validator,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        AuthenticateByKey::getApiKey($request);

        $command = $this->denormalizer->denormalize(
            array_merge((array)$request->getParsedBody(), [
                'id' => Route::getArgumentToInt($request, 'id'),
            ]),
            Command::class
        );

        $this->validator->validate($command);

        $this->handler->handle($command);

        return new JsonDataSuccessResponse();
    }
}
