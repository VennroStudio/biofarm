<?php

declare(strict_types=1);

namespace App\Http\Action\V1\ISRC;

use App\Components\ISRCSearch\ISRCSearch;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use ZayMedia\Shared\Components\Router\Route;
use ZayMedia\Shared\Helpers\OpenApi\ResponseSuccessful;
use ZayMedia\Shared\Helpers\OpenApi\Security;
use ZayMedia\Shared\Http\Response\JsonDataResponse;

#[OA\Get(
    path: '/isrc/{code}',
    description: 'Получение информации по ISRC',
    summary: 'Получение информации по ISRC',
    security: [Security::API_KEY],
    tags: ['ISRC'],
    responses: [new ResponseSuccessful()]
)]
#[OA\Parameter(
    name: 'code',
    description: 'code',
    in: 'path',
    required: true,
    schema: new OA\Schema(
        type: 'string',
    ),
    example: 'GBAYE1901301'
)]
final readonly class ISRCSearchAction implements RequestHandlerInterface
{
    public function __construct(
        private ISRCSearch $ISRCSearch
    ) {}

    public function handle(Request $request): Response
    {
        $code = Route::getArgument($request, 'code');

        $data = $this->ISRCSearch->getInfo($code);

        return new JsonDataResponse($data);
    }
}
