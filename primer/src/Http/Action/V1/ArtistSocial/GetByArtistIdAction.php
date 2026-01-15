<?php

declare(strict_types=1);

namespace App\Http\Action\V1\ArtistSocial;

use App\Modules\Query\Artists\GetArtistSocialsArray\Fetcher;
use App\Modules\Query\Artists\GetArtistSocialsArray\Query;
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
    path: '/artists/{id}/socials',
    description: 'Получение списка соц сетей',
    summary: 'Получение списка соц сетей',
    security: [Security::BEARER_AUTH],
    tags: ['Artists (Socials)'],
    responses: [new ResponseSuccessful()]
)]
#[OA\Parameter(
    name: 'id',
    description: 'Идентификатор артиста',
    in: 'path',
    required: true,
    schema: new OA\Schema(
        type: 'integer',
        format: 'int64'
    ),
    example: 1
)]
final readonly class GetByArtistIdAction implements RequestHandlerInterface
{
    public function __construct(
        private Fetcher $fetcher,
        private Validator $validator,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $query = new Query(
            artisId: Route::getArgumentToInt($request, 'id')
        );

        $this->validator->validate($query);

        $result = $this->fetcher->fetch($query);

        return new JsonDataResponse(['items' => $result]);
    }
}
