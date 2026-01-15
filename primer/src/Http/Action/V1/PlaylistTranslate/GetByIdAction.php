<?php

declare(strict_types=1);

namespace App\Http\Action\V1\PlaylistTranslate;

use App\Modules\Entity\PlaylistTranslate\PlaylistTranslateRepository;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ZayMedia\Shared\Components\Router\Route;
use ZayMedia\Shared\Helpers\OpenApi\ResponseSuccessful;
use ZayMedia\Shared\Http\Response\JsonDataResponse;

#[OA\Get(
    path: '/playlists/{id}/translates/{translateId}',
    description: 'Получение информации о переводе',
    summary: 'Получение информации о переводе',
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
final readonly class GetByIdAction implements RequestHandlerInterface
{
    public function __construct(
        private PlaylistTranslateRepository $playlistTranslateRepository,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $translate = $this->playlistTranslateRepository->getById(
            id: Route::getArgumentToInt($request, 'translateId'),
        );

        return new JsonDataResponse([
            'id'            => $translate->getId(),
            'lang'          => $translate->getLang(),
            'name'          => $translate->getName(),
            'description'   => $translate->getDescription(),
        ]);
    }
}
