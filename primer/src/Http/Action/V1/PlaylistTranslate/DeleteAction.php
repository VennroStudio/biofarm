<?php

declare(strict_types=1);

namespace App\Http\Action\V1\PlaylistTranslate;

use App\Modules\Command\PlaylistTranslate\Delete\Command;
use App\Modules\Command\PlaylistTranslate\Delete\Handler;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ZayMedia\Shared\Components\Router\Route;
use ZayMedia\Shared\Components\Validator\Validator;
use ZayMedia\Shared\Helpers\OpenApi\ResponseSuccessful;
use ZayMedia\Shared\Helpers\OpenApi\Security;
use ZayMedia\Shared\Http\Response\JsonDataSuccessResponse;

#[OA\Delete(
    path: '/playlists/{id}/translates/{translateId}',
    description: 'Удаление перевода',
    summary: 'Удаление перевода',
    security: [Security::BEARER_AUTH],
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
final readonly class DeleteAction implements RequestHandlerInterface
{
    public function __construct(
        private Handler $handler,
        private Validator $validator
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $command = new Command(
            playlistId: Route::getArgumentToInt($request, 'id'),
            translateId: Route::getArgumentToInt($request, 'translateId')
        );

        $this->validator->validate($command);

        $this->handler->handle($command);

        return new JsonDataSuccessResponse();
    }
}
