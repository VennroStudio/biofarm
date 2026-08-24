<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\PromoCode;

use App\Components\Exception\DomainExceptionModule;
use App\Components\Http\Response\JsonDataResponse;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

final readonly class DeletePromoCodeAction implements RequestHandlerInterface
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
            throw new DomainExceptionModule('order', 'error.promo_code_not_found', 35, status: 404);
        }

        $deleted = $this->connection->delete('promo_codes', ['id' => $id]);
        if ($deleted === 0) {
            throw new DomainExceptionModule('order', 'error.promo_code_not_found', 35, status: 404);
        }

        return new JsonDataResponse(true);
    }
}
