<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Integration;

use App\Components\Http\Response\JsonDataResponse;
use App\Components\Integration\IntegrationErrorLogger;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

final readonly class MarkIntegrationErrorReadAction implements RequestHandlerInterface
{
    public function __construct(
        private IntegrationErrorLogger $errorLogger,
    ) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = (int)RouteContext::fromRequest($request)
            ->getRoute()
            ?->getArgument('id');

        $this->errorLogger->markRead($id);

        return new JsonDataResponse(['id' => $id]);
    }
}
