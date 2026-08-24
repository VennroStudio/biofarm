<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Certificate;

use App\Components\Http\Response\JsonDataResponse;
use App\Components\Exception\DomainExceptionModule;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

final readonly class DeleteCertificateAction implements RequestHandlerInterface
{
    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = (int)(RouteContext::fromRequest($request)->getRoute()?->getArgument('id') ?? 0);
        if ($id <= 0) {
            throw new DomainExceptionModule('certificate', 'error.certificate_not_found', 3, status: 404);
        }

        if ($this->connection->delete('certificates', ['id' => $id]) === 0) {
            throw new DomainExceptionModule('certificate', 'error.certificate_not_found', 4, status: 404);
        }

        return new JsonDataResponse(true);
    }
}
