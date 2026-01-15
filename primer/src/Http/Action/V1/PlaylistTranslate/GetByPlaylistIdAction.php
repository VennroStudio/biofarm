<?php

declare(strict_types=1);

namespace App\Http\Action\V1\PlaylistTranslate;

use App\Modules\Query\Playlists\GetTranslatesArray\Fetcher;
use App\Modules\Query\Playlists\GetTranslatesArray\Query;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ZayMedia\Shared\Components\Router\Route;
use ZayMedia\Shared\Components\Validator\Validator;
use ZayMedia\Shared\Helpers\OpenApi\ResponseSuccessful;
use ZayMedia\Shared\Helpers\OpenApi\Security;
use ZayMedia\Shared\Http\Response\JsonDataResponse;

#[OA\Get(
    path: '/playlists/{id}/translates',
    description: 'Получение списка переводов',
    summary: 'Получение списка переводов',
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
final readonly class GetByPlaylistIdAction implements RequestHandlerInterface
{
    public function __construct(
        private Fetcher $fetcher,
        private Validator $validator,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $query = new Query(
            playlistId: Route::getArgumentToInt($request, 'id')
        );

        $this->validator->validate($query);

        $result = $this->fetcher->fetch($query);

        return new JsonDataResponse(['items' => $result]);
    }
}
