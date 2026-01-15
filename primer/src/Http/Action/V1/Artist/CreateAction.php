<?php

declare(strict_types=1);

namespace App\Http\Action\V1\Artist;

use App\Modules\Command\Artist\Create\Command;
use App\Modules\Command\Artist\Create\Handler;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ZayMedia\Shared\Components\Serializer\Denormalizer;
use ZayMedia\Shared\Components\Validator\Validator;
use ZayMedia\Shared\Helpers\OpenApi\ResponseSuccessful;
use ZayMedia\Shared\Helpers\OpenApi\Security;
use ZayMedia\Shared\Http\Response\JsonDataSuccessResponse;

#[OA\Post(
    path: '/artists',
    description: 'Добавление артиста',
    summary: 'Добавление артиста',
    security: [Security::BEARER_AUTH],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'unionId',
                    type: 'integer',
                    example: 0,
                ),
                new OA\Property(
                    property: 'name',
                    type: 'string',
                    example: 'Gorillaz'
                ),
            ]
        )
    ),
    tags: ['Artists'],
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
            $request->getParsedBody(),
            Command::class
        );

        $this->validator->validate($command);

        $this->handler->handle($command);

        return new JsonDataSuccessResponse();
    }
}
