<?php

declare(strict_types=1);

namespace App\Http\Action\V1\Stats;

use App\Modules\Command\ArtistProblematic\UpdateStatus\Command;
use App\Modules\Command\ArtistProblematic\UpdateStatus\Handler;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Throwable;
use ZayMedia\Shared\Components\Serializer\Denormalizer;
use ZayMedia\Shared\Components\Validator\Validator;
use ZayMedia\Shared\Helpers\OpenApi\ResponseSuccessful;
use ZayMedia\Shared\Helpers\OpenApi\Security;
use ZayMedia\Shared\Http\Response\JsonDataSuccessResponse;

#[OA\Put(
    path: '/stats/artist-problematic/status',
    description: 'Обновление статуса проблемного артиста',
    summary: 'Обновление статуса проблемного артиста',
    security: [Security::BEARER_AUTH],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'artistId',
                    description: 'ID артиста (один или массив)',
                    oneOf: [
                        new OA\Schema(type: 'integer', example: 12345),
                        new OA\Schema(type: 'array', items: new OA\Items(type: 'integer'), example: [12345, 12346, 12347]),
                    ]
                ),
                new OA\Property(
                    property: 'status',
                    type: 'integer',
                    example: 1
                ),
            ]
        )
    ),
    tags: ['ArtistProblematic'],
    responses: [new ResponseSuccessful()]
)]
final readonly class ArtistProblematicUpdateStatus implements RequestHandlerInterface
{
    public function __construct(
        private Denormalizer $denormalizer,
        private Handler $handler,
        private Validator $validator,
    ) {}

    /**
     * @throws ExceptionInterface
     * @throws Throwable
     */
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
