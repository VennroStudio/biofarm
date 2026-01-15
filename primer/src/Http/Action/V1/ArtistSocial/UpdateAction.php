<?php

declare(strict_types=1);

namespace App\Http\Action\V1\ArtistSocial;

use App\Modules\Command\ArtistSocial\Update\Command;
use App\Modules\Command\ArtistSocial\Update\Handler;
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

#[OA\Put(
    path: '/artists/{id}/socials/{socialId}',
    description: 'Редактирование соц сети',
    summary: 'Редактирование соц сети',
    security: [Security::BEARER_AUTH],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'url',
                    type: 'string',
                    example: 'https://listen.tidal.com/artist/8853'
                ),
                new OA\Property(
                    property: 'description',
                    type: 'string',
                    example: 'Gorillaz'
                ),
            ]
        )
    ),
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
                'artistId' => Route::getArgumentToInt($request, 'id'),
                'socialId' => Route::getArgumentToInt($request, 'socialId'),
            ]),
            Command::class
        );

        $this->validator->validate($command);

        $this->handler->handle($command);

        return new JsonDataSuccessResponse();
    }
}
