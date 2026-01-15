<?php

declare(strict_types=1);

namespace App\Http\Action\V1\Youtube;

use App\Components\YoutubeGrab\YoutubeGrab;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use ZayMedia\Shared\Helpers\OpenApi\ResponseSuccessful;
use ZayMedia\Shared\Helpers\OpenApi\Security;
use ZayMedia\Shared\Http\Response\JsonDataResponse;

#[OA\Get(
    path: '/youtube',
    description: 'Поиск аудиозаписи в YouTube Music',
    summary: 'Поиск аудиозаписи в YouTube Music',
    security: [Security::API_KEY],
    tags: ['Youtube'],
    responses: [new ResponseSuccessful()]
)]
#[OA\Parameter(
    name: 'search',
    description: 'Поисковый запрос',
    in: 'query',
    required: false,
    schema: new OA\Schema(
        type: 'string',
    ),
    example: 'Плохая Барби - Алена UNI'
)]
final class YoutubeSearchAction implements RequestHandlerInterface
{
    public function handle(Request $request): Response
    {
        /** @var array{search: string|null} $data */
        $data = $request->getQueryParams();

        $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Safari/605.1.15';

        $youtubeGrab = new YoutubeGrab(
            cookies: __DIR__ . '/cookie.txt',
            userAgent: $userAgent
        );
        $data = $youtubeGrab->musicSearch($data['search'] ?? '');

        return new JsonDataResponse($data);
    }
}
