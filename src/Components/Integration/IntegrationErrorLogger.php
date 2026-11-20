<?php

declare(strict_types=1);

namespace App\Components\Integration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use JsonException;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class IntegrationErrorLogger
{
    public function __construct(
        private Connection $connection,
        private LoggerInterface $logger,
    ) {}

    /**
     * @param array<string, mixed> $context
     */
    public function log(
        string $service,
        string $scenario,
        string $operation,
        string $message,
        ?int $httpStatus = null,
        ?string $responseBody = null,
        ?string $localEntityType = null,
        ?string $localEntityId = null,
        array $context = [],
    ): void {
        try {
            $this->connection->insert('integration_error_logs', [
                'service'           => mb_substr($service, 0, 50),
                'scenario'          => mb_substr($scenario, 0, 80),
                'operation'         => mb_substr($operation, 0, 100),
                'local_entity_type' => $localEntityType !== null ? mb_substr($localEntityType, 0, 80) : null,
                'local_entity_id'   => $localEntityId !== null ? mb_substr($localEntityId, 0, 100) : null,
                'message'           => mb_substr($message, 0, 1000),
                'http_status'       => $httpStatus,
                'response_body'     => $responseBody !== null ? mb_substr($responseBody, 0, 20000) : null,
                'context'           => $this->json($context),
                'is_read'           => 0,
                'created_at'        => gmdate('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $exception) {
            $this->logger->warning('Integration error log was not saved.', [
                'service'  => $service,
                'scenario' => $scenario,
                'error'    => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @return array{count: int, items: list<array<string, mixed>>}
     * @throws Exception
     */
    public function list(int $limit = 200): array
    {
        $rows = $this->connection->createQueryBuilder()
            ->select(
                'id',
                'service',
                'scenario',
                'operation',
                'local_entity_type',
                'local_entity_id',
                'message',
                'http_status',
                'response_body',
                'context',
                'is_read',
                'created_at',
                'read_at',
            )
            ->from('integration_error_logs')
            ->orderBy('is_read', 'ASC')
            ->addOrderBy('created_at', 'DESC')
            ->setMaxResults($limit)
            ->executeQuery()
            ->fetchAllAssociative();

        $count = (int)$this->connection->fetchOne('SELECT COUNT(id) FROM integration_error_logs');

        return [
            'count' => $count,
            'items' => array_map($this->mapRow(...), $rows),
        ];
    }

    /**
     * @throws Exception
     */
    public function markRead(int $id): void
    {
        $this->connection->update(
            'integration_error_logs',
            ['is_read' => 1, 'read_at' => gmdate('Y-m-d H:i:s')],
            ['id'      => $id],
        );
    }

    /**
     * @throws Exception
     */
    public function markAllRead(): void
    {
        $this->connection->executeStatement(
            'UPDATE integration_error_logs SET is_read = 1, read_at = UTC_TIMESTAMP() WHERE is_read = 0'
        );
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function mapRow(array $row): array
    {
        return [
            'id'                => (int)$row['id'],
            'service'           => (string)$row['service'],
            'scenario'          => (string)$row['scenario'],
            'operation'         => (string)$row['operation'],
            'local_entity_type' => $row['local_entity_type'] !== null ? (string)$row['local_entity_type'] : null,
            'local_entity_id'   => $row['local_entity_id'] !== null ? (string)$row['local_entity_id'] : null,
            'message'           => (string)$row['message'],
            'http_status'       => $row['http_status'] !== null ? (int)$row['http_status'] : null,
            'response_body'     => $row['response_body'] !== null ? (string)$row['response_body'] : null,
            'context'           => $this->decodeContext($row['context'] ?? null),
            'is_read'           => (bool)(int)$row['is_read'],
            'created_at'        => (string)$row['created_at'],
            'read_at'           => $row['read_at'] !== null ? (string)$row['read_at'] : null,
        ];
    }

    /**
     * @param array<string, mixed> $context
     */
    private function json(array $context): ?string
    {
        if ($context === []) {
            return null;
        }

        try {
            return json_encode($context, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (JsonException) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeContext(mixed $context): array
    {
        if (!\is_string($context) || $context === '') {
            return [];
        }

        try {
            $decoded = json_decode($context, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        return \is_array($decoded) ? $decoded : [];
    }
}
