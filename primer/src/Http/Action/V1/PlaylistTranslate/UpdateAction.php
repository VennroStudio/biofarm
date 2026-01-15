<?php

declare(strict_types=1);

namespace App\Http\Action\V1\PlaylistTranslate;

use App\Modules\Command\PlaylistTranslate\Update\Command;
use App\Modules\Command\PlaylistTranslate\Update\Handler;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ZayMedia\Shared\Components\Router\Route;
use ZayMedia\Shared\Components\Serializer\Denormalizer;
use ZayMedia\Shared\Components\Validator\Validator;
use ZayMedia\Shared\Helpers\OpenApi\ResponseSuccessful;
use ZayMedia\Shared\Helpers\OpenApi\Security;
use ZayMedia\Shared\Http\Response\JsonDataSuccessResponse;

#[OA\Post(
    path: '/playlists/{id}/translates/{translateId}',
    description: 'Редактирование перевода',
    summary: 'Редактирование перевода',
    security: [Security::BEARER_AUTH],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'name',
                    type: 'string',
                    example: 'Gorillaz'
                ),
                new OA\Property(
                    property: 'description',
                    type: 'string',
                    example: 'Gorillaz'
                ),
            ]
        )
    ),
    tags: ['Playlists (Translates)'],
    responses: [new ResponseSuccessful()]
)]
#[OA\Parameter(
    name: 'id',
    description: 'Идентификатор плейлиста',
    in: 'path',
    required: true,
    schema: new OA\Schema(
        type: 'integer',
        format: 'int64'
    ),
    example: 1
)]
#[OA\Parameter(
    name: 'translateId',
    description: 'Идентификатор перевода',
    in: 'path',
    required: true,
    schema: new OA\Schema(
        type: 'integer',
        format: 'int64'
    ),
    example: 1
)]
final readonly class UpdateAction implements RequestHandlerInterface
{
    public function __construct(
        private Denormalizer $denormalizer,
        private Handler $handler,
        private Validator $validator,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $command = $this->denormalizer->denormalize(
            array_merge((array)$request->getParsedBody(), [
                'playlistId' => Route::getArgumentToInt($request, 'id'),
                'translateId' => Route::getArgumentToInt($request, 'translateId'),
                'filePath' => $_FILES['file']['tmp_name'] ?? null,
            ]),
            Command::class
        );

        $this->validator->validate($command);

        $this->handler->handle($command);

        return new JsonDataSuccessResponse();
    }
}
