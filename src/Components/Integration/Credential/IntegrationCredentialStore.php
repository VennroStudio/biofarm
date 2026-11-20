<?php

declare(strict_types=1);

namespace App\Components\Integration\Credential;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Throwable;

final readonly class IntegrationCredentialStore
{
    public function __construct(
        private Connection $connection,
        private SecretCipher $cipher,
    ) {}

    /**
     * @throws Exception
     */
    public function save(string $key, string $plainValue): void
    {
        $now = gmdate('Y-m-d H:i:s');
        $this->connection->executeStatement(
            'INSERT INTO integration_credentials (`key`, encrypted_value, created_at, updated_at)
             VALUES (:key, :value, :createdAt, NULL)
             ON DUPLICATE KEY UPDATE encrypted_value = VALUES(encrypted_value), updated_at = :updatedAt',
            [
                'key'       => $key,
                'value'     => $this->cipher->encrypt($plainValue),
                'createdAt' => $now,
                'updatedAt' => $now,
            ],
        );
    }

    public function get(string $key): ?string
    {
        try {
            $value = $this->connection->fetchOne(
                'SELECT encrypted_value FROM integration_credentials WHERE `key` = :key LIMIT 1',
                ['key' => $key],
            );
        } catch (Throwable) {
            return null;
        }

        if (!\is_string($value) || $value === '') {
            return null;
        }

        try {
            return $this->cipher->decrypt($value);
        } catch (Throwable) {
            return null;
        }
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }
}
