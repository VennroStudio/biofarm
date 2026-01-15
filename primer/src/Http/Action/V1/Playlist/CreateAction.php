<?php

declare(strict_types=1);

namespace App\Http\Action\V1\Playlist;

use App\Modules\Command\Playlist\Create\Command;
use App\Modules\Command\Playlist\Create\Handler;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ZayMedia\Shared\Components\Serializer\Denormalizer;
use ZayMedia\Shared\Components\Validator\Validator;
use ZayMedia\Shared\Helpers\OpenApi\ResponseSuccessful;
use ZayMedia\Shared\Helpers\OpenApi\Security;
use ZayMedia\Shared\Http\Response\JsonDataSuccessResponse;

#[OA\Post(
    path: '/playlists',
    description: 'Добавление плейлиста',
    summary: 'Добавление плейлиста',
    security: [Security::BEARER_AUTH],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'categoryId',
                    type: 'integer',
                    example: null,
                ),
                new OA\Property(
                    property: 'name',
                    type: 'string',
                    example: 'Playlist'
                ),
                new OA\Property(
                    property: 'url',
                    type: 'string',
                    example: 'xxx'
                ),
            ]
        )
    ),
    tags: ['Playlists'],
    responses: [new ResponseSuccessful()]
)]
final readonly class CreateAction implements RequestHandlerInterface
{
    public function __construct(
        private Denormalizer $denormalizer,
        private Handler $handler,
        private Validator $validator,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $command = $this->denormalizer->denormalize(
            $request->getParsedBody(),
            Command::class
        );

        $this->validator->validate($command);

        $this->handler->handle($command);

        return new JsonDataSuccessResponse();
    }
}
