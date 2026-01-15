<?php

declare(strict_types=1);

namespace App\Http\Action\V1\Spotify;

use App\Modules\Query\GetSpotifyTokens\Fetcher;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ZayMedia\Shared\Helpers\OpenApi\ResponseSuccessful;
use ZayMedia\Shared\Helpers\OpenApi\Security;
use ZayMedia\Shared\Http\Response\JsonDataResponse;

#[OA\Get(
    path: '/spotify-tokens',
    description: 'Получение списка токенов Spotify',
    summary: 'Получение списка токенов Spotify',
    security: [Security::BEARER_AUTH],
    tags: ['Spotify'],
    responses: [new ResponseSuccessful()]
)]
final readonly class GetTokensAction implements RequestHandlerInterface
{
    public function __construct(
        private Fetcher $fetcher,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $result = $this->fetcher->fetch();

        return new JsonDataResponse(['items' => $result]);
    }
}
