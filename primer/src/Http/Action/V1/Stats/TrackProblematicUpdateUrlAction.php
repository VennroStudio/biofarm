<?php

declare(strict_types=1);

namespace App\Http\Action\V1\Stats;

use App\Modules\Command\TrackProblematic\UpdateUrl\Command;
use App\Modules\Command\TrackProblematic\UpdateUrl\Handler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;
use ZayMedia\Shared\Components\Router\Route;
use ZayMedia\Shared\Http\Response\JsonDataResponse;

final readonly class TrackProblematicUpdateUrlAction implements RequestHandlerInterface
{
    public function __construct(
        private Handler $handler,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(Request $request): Response
    {
        $id = Route::getArgumentToInt($request, 'id');

        /** @var array{tidal_url?: string, spotify_url?: string} $parsedBody */
        $parsedBody = $request->getParsedBody();

        $command = new Command(
            trackProblematicId: $id,
            tidalUrl: $parsedBody['tidal_url'] ?? null,
            spotifyUrl: $parsedBody['spotify_url'] ?? null
        );

        $this->handler->handle($command);

        return new JsonDataResponse([]);
    }
}
