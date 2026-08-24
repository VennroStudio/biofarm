<?php

declare(strict_types=1);

namespace App\Http\Action\v1\User;

use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataItemsResponse;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class GetUserAddressesAction implements RequestHandlerInterface
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

        if (!$this->hasTable()) {
            return new JsonDataItemsResponse(0, []);
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, label, name, phone, email, city, address, postal_code, comment, is_default, created_at, updated_at
             FROM user_addresses
             WHERE user_id = :userId AND deleted_at IS NULL
             ORDER BY is_default DESC, id DESC',
            ['userId' => $identity->id],
        );

        return new JsonDataItemsResponse(\count($rows), array_map($this->map(...), $rows));
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, bool|int|string|null>
     */
    private function map(array $row): array
    {
        return [
            'id'          => (int)$row['id'],
            'label'       => (string)$row['label'],
            'name'        => (string)$row['name'],
            'phone'       => (string)$row['phone'],
            'email'       => (string)$row['email'],
            'city'        => (string)$row['city'],
            'address'     => (string)$row['address'],
            'postal_code' => (string)$row['postal_code'],
            'comment'     => $row['comment'] !== null ? (string)$row['comment'] : null,
            'is_default'  => (bool)(int)$row['is_default'],
            'created_at'  => (string)$row['created_at'],
            'updated_at'  => $row['updated_at'] !== null ? (string)$row['updated_at'] : null,
        ];
    }

    private function hasTable(): bool
    {
        try {
            return $this->connection->createSchemaManager()->tablesExist(['user_addresses']);
        } catch (Exception) {
            return false;
        }
    }
}
