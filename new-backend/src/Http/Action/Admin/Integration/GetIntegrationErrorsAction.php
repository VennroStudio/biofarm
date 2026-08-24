<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Integration;

use App\Components\Http\Response\JsonDataItemsResponse;
use App\Components\Integration\IntegrationErrorLogger;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class GetIntegrationErrorsAction implements RequestHandlerInterface
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
        $result = $this->errorLogger->list();

        return new JsonDataItemsResponse($result['count'], $result['items']);
    }
}
