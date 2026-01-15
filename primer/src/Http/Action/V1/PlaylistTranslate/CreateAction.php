<?php

declare(strict_types=1);

namespace App\Http\Action\V1\PlaylistTranslate;

use App\Modules\Command\PlaylistTranslate\Create\Command;
use App\Modules\Command\PlaylistTranslate\Create\Handler;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ZayMedia\Shared\Components\Router\Route;
use ZayMedia\Shared\Components\Serializer\Denormalizer;
use ZayMedia\Shared\Components\Validator\Validator;
use ZayMedia\Shared\Helpers\OpenApi\ResponseSuccessful;
use ZayMedia\Shared\Helpers\OpenApi\Security;
use ZayMedia\Shared\Http\Response\JsonDataResponse;

#[OA\Post(
    path: '/playlists/{id}/translates',
    description: 'Добавление перевода',
    summary: 'Добавление перевода',
    security: [Security::BEARER_AUTH],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'name',
                    type: 'string',
                    example: 'Наименование плейлиста',
                ),
                new OA\Property(
                    property: 'description',
                    type: 'string',
                    example: 'Описание плейлиста',
                ),
            ]
        )
    ),
    tags: ['Playlists (Translates)'],
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
            array_merge((array)$request->getParsedBody(), [
                'playlistId' => Route::getArgumentToInt($request, 'id'),
                'filePath' => $_FILES['file']['tmp_name'] ?? null,
            ]),
            Command::class
        );

        $this->validator->validate($command);

        $translate = $this->handler->handle($command);

        return new JsonDataResponse(['id' => $translate->getId()]);
    }
}
