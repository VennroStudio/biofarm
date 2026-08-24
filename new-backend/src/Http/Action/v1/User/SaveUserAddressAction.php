<?php

declare(strict_types=1);

namespace App\Http\Action\v1\User;

use App\Components\Exception\DomainExceptionModule;
use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\Router\Route;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use InvalidArgumentException;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class SaveUserAddressAction implements RequestHandlerInterface
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
        $payload = (array)$request->getParsedBody();
        $addressId = $this->routeId($request);
        $data = $this->payload($payload);

        if ($addressId !== null && !$this->addressExists($identity->id, $addressId)) {
            throw new DomainExceptionModule('user', 'error.address_not_found', 44, status: 404);
        }

        if ($data['is_default'] || !$this->hasAddresses($identity->id, $addressId)) {
            $data['is_default'] = true;
            $this->connection->executeStatement(
                'UPDATE user_addresses SET is_default = 0, updated_at = UTC_TIMESTAMP() WHERE user_id = :userId AND deleted_at IS NULL',
                ['userId' => $identity->id],
            );
        }

        if ($addressId === null) {
            $this->connection->insert('user_addresses', [
                'user_id'     => $identity->id,
                'label'       => $data['label'],
                'name'        => $data['name'],
                'phone'       => $data['phone'],
                'email'       => $data['email'],
                'city'        => $data['city'],
                'address'     => $data['address'],
                'postal_code' => $data['postal_code'],
                'comment'     => $data['comment'],
                'is_default'  => $data['is_default'] ? 1 : 0,
                'created_at'  => gmdate('Y-m-d H:i:s'),
            ]);
            $addressId = (int)$this->connection->lastInsertId();
            $status = 201;
        } else {
            $this->connection->update('user_addresses', [
                'label'       => $data['label'],
                'name'        => $data['name'],
                'phone'       => $data['phone'],
                'email'       => $data['email'],
                'city'        => $data['city'],
                'address'     => $data['address'],
                'postal_code' => $data['postal_code'],
                'comment'     => $data['comment'],
                'is_default'  => $data['is_default'] ? 1 : 0,
                'updated_at'  => gmdate('Y-m-d H:i:s'),
            ], ['id' => $addressId, 'user_id' => $identity->id]);
            $status = 200;
        }

        return new JsonDataResponse($this->address($identity->id, $addressId), $status);
    }

    private function routeId(ServerRequestInterface $request): ?int
    {
        try {
            $id = Route::getArgumentToInt($request, 'id');
        } catch (InvalidArgumentException) {
            return null;
        }

        return $id > 0 ? $id : null;
    }

    /**
     * @param array<array-key, mixed> $payload
     * @return array{label: string, name: string, phone: string, email: string, city: string, address: string, postal_code: string, comment: string|null, is_default: bool}
     */
    private function payload(array $payload): array
    {
        $city = $this->string($payload, 'city');
        $address = $this->string($payload, 'address');

        if ($city === '' || $address === '') {
            throw new DomainExceptionModule('user', 'error.address_required', 45, status: 422);
        }

        return [
            'label'       => $this->string($payload, 'label') ?: 'Адрес',
            'name'        => $this->string($payload, 'name'),
            'phone'       => $this->string($payload, 'phone'),
            'email'       => $this->string($payload, 'email'),
            'city'        => $city,
            'address'     => $address,
            'postal_code' => $this->string($payload, 'postalCode', 'postal_code'),
            'comment'     => $this->nullableString($payload, 'comment'),
            'is_default'  => $this->bool($payload['isDefault'] ?? $payload['is_default'] ?? false),
        ];
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    private function string(array $payload, string $firstKey, ?string $secondKey = null): string
    {
        $value = $payload[$firstKey] ?? ($secondKey !== null ? ($payload[$secondKey] ?? '') : '');

        return \is_scalar($value) ? trim((string)$value) : '';
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    private function nullableString(array $payload, string $key): ?string
    {
        $value = $this->string($payload, $key);

        return $value !== '' ? $value : null;
    }

    private function bool(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1' || $value === 'true';
    }

    /**
     * @throws Exception
     */
    private function hasAddresses(int $userId, ?int $excludeId): bool
    {
        $sql = 'SELECT COUNT(*) FROM user_addresses WHERE user_id = :userId AND deleted_at IS NULL';
        $params = ['userId' => $userId];

        if ($excludeId !== null) {
            $sql .= ' AND id != :excludeId';
            $params['excludeId'] = $excludeId;
        }

        return (int)$this->connection->fetchOne($sql, $params) > 0;
    }

    /**
     * @throws Exception
     */
    private function addressExists(int $userId, int $addressId): bool
    {
        return (int)$this->connection->fetchOne(
            'SELECT COUNT(*) FROM user_addresses WHERE id = :id AND user_id = :userId AND deleted_at IS NULL',
            ['id' => $addressId, 'userId' => $userId],
        ) > 0;
    }

    /**
     * @return array<string, bool|int|string|null>
     * @throws Exception
     */
    private function address(int $userId, int $addressId): array
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, label, name, phone, email, city, address, postal_code, comment, is_default, created_at, updated_at
             FROM user_addresses
             WHERE id = :id AND user_id = :userId AND deleted_at IS NULL',
            ['id' => $addressId, 'userId' => $userId],
        );

        if ($row === false) {
            return [];
        }

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
}
