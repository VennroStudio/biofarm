<?php

declare(strict_types=1);

namespace App\Http\Action\V1\ArtistSocial;

use App\Modules\Entity\ArtistSocial\ArtistSocialRepository;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ZayMedia\Shared\Components\Router\Route;
use ZayMedia\Shared\Helpers\OpenApi\ResponseSuccessful;
use ZayMedia\Shared\Http\Response\JsonDataResponse;

#[OA\Get(
    path: '/artists/{id}/socials/{socialId}',
    description: 'Получение информации о соц сети',
    summary: 'Получение информации о соц сети',
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
#[OA\Parameter(
    name: 'socialId',
    description: 'Идентификатор соц сети',
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
        private ArtistSocialRepository $artistSocialRepository,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $social = $this->artistSocialRepository->getById(
            id: Route::getArgumentToInt($request, 'socialId'),
        );

        return new JsonDataResponse([
            'id' => $social->getId(),
            'type' => $social->getType(),
            'url' => $social->getUrl(),
            'description' => $social->getDescription(),
        ]);
    }
}
