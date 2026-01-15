<?php

declare(strict_types=1);

namespace App\Http\Action\V1\ArtistSocial;

use App\Modules\Command\ArtistSocial\Create\Command;
use App\Modules\Command\ArtistSocial\Create\Handler;
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
    path: '/artists/{id}/socials',
    description: 'Добавление соц сети',
    summary: 'Добавление соц сети',
    security: [Security::BEARER_AUTH],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'type',
                    type: 'integer',
                    example: 0,
                ),
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
                'artistId' => Route::getArgumentToInt($request, 'id'),
            ]),
            Command::class
        );

        $this->validator->validate($command);

        $artistSocial = $this->handler->handle($command);

        $id = $artistSocial->getId();

        return new JsonDataResponse(['id' => $id]);
    }
}
