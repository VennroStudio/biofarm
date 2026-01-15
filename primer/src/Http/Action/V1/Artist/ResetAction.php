<?php

declare(strict_types=1);

namespace App\Http\Action\V1\Artist;

use App\Modules\Command\Artist\ResetChecking\Command;
use App\Modules\Command\Artist\ResetChecking\Handler;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ZayMedia\Shared\Components\Router\Route;
use ZayMedia\Shared\Components\Validator\Validator;
use ZayMedia\Shared\Helpers\OpenApi\ResponseSuccessful;
use ZayMedia\Shared\Helpers\OpenApi\Security;
use ZayMedia\Shared\Http\Response\JsonDataSuccessResponse;

#[OA\Post(
    path: '/artists/{id}/reset',
    description: 'Обнуление времен проверок',
    summary: 'Обнуление времен проверок',
    security: [Security::BEARER_AUTH],
    tags: ['Artists'],
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
final readonly class ResetAction implements RequestHandlerInterface
{
    public function __construct(
        private Handler $handler,
        private Validator $validator,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $command = new Command(
            artistId: Route::getArgumentToInt($request, 'id')
        );

        $this->validator->validate($command);

        $this->handler->handle($command);

        return new JsonDataSuccessResponse();
    }
}
