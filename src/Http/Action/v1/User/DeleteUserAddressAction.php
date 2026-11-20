<?php

declare(strict_types=1);

namespace App\Http\Action\v1\User;

use App\Components\Exception\DomainExceptionModule;
use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataSuccessResponse;
use App\Components\Router\Route;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class DeleteUserAddressAction implements RequestHandlerInterface
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
        $identity = RequestIdentity::get($request);
        $addressId = Route::getArgumentToInt($request, 'id');

        $affected = $this->connection->executeStatement(
            'UPDATE user_addresses
             SET deleted_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP()
             WHERE id = :id AND user_id = :userId AND deleted_at IS NULL',
            ['id' => $addressId, 'userId' => $identity->id],
        );

        if ($affected === 0) {
            throw new DomainExceptionModule('user', 'error.address_not_found', 44, status: 404);
        }

        return new JsonDataSuccessResponse(1, 200);
    }
}
