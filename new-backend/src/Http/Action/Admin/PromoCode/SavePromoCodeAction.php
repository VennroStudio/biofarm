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

final readonly class SavePromoCodeAction implements RequestHandlerInterface
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
        $id = $this->routeId($request);
        $payload = (array)$request->getParsedBody();
        $code = $this->normalizeCode($payload['code'] ?? '');

        if ($code === '') {
            throw new DomainExceptionModule('order', 'error.promo_code_required', 31, status: 422);
        }

        $type = (string)($payload['type'] ?? 'percent');
        if (!\in_array($type, ['percent', 'fixed'], true)) {
            throw new DomainExceptionModule('order', 'error.promo_code_type_invalid', 32, status: 422);
        }

        $value = max(0, (int)($payload['value'] ?? 0));
        if ($value <= 0) {
            throw new DomainExceptionModule('order', 'error.promo_code_value_required', 33, status: 422);
        }

        $this->assertCodeFree($code, $id);

        $startsAt = $this->nullableDate($payload['starts_at'] ?? null);
        $endsAt = $this->nullableDate($payload['ends_at'] ?? null);
        if ($startsAt !== null && $endsAt !== null && $startsAt > $endsAt) {
            throw new DomainExceptionModule('order', 'error.promo_code_period_invalid', 35, status: 422);
        }

        $data = [
            'code'            => $code,
            'type'            => $type,
            'value'           => $type === 'percent' ? min($value, 100) : $value,
            'min_order_total' => max(0, (int)($payload['min_order_total'] ?? 0)),
            'starts_at'       => $startsAt,
            'ends_at'         => $endsAt,
            'usage_limit'     => $this->nullablePositiveInt($payload['usage_limit'] ?? null),
            'is_active'       => !isset($payload['is_active']) || (bool)$payload['is_active'] ? 1 : 0,
            'updated_at'      => gmdate('Y-m-d H:i:s'),
        ];

        $isCreate = $id === null;
        if ($id !== null) {
            $this->assertExists($id);
            $this->connection->update('promo_codes', $data, ['id' => $id]);
        } else {
            $data['used_count'] = 0;
            $data['created_at'] = gmdate('Y-m-d H:i:s');
            $this->connection->insert('promo_codes', $data);
            $id = (int)$this->connection->lastInsertId();
        }

        return new JsonDataResponse(['id' => $id], $isCreate ? 201 : 200);
    }

    private function routeId(ServerRequestInterface $request): ?int
    {
        $argument = RouteContext::fromRequest($request)
            ->getRoute()
            ?->getArgument('id');

        return $argument !== null ? (int)$argument : null;
    }

    private function normalizeCode(mixed $value): string
    {
        $code = mb_strtoupper(trim((string)$value));
        $code = preg_replace('/[^A-Z0-9_-]+/u', '', $code) ?? '';

        return mb_substr($code, 0, 100);
    }

    /**
     * @throws Exception
     */
    private function assertCodeFree(string $code, ?int $id): void
    {
        /** @var false|int|string $existingId */
        $existingId = $this->connection->fetchOne(
            'SELECT id FROM promo_codes WHERE code = :code LIMIT 1',
            ['code' => $code],
        );

        if ($existingId !== false && (int)$existingId !== $id) {
            throw new DomainExceptionModule('order', 'error.promo_code_already_exists', 34, status: 422);
        }
    }

    /**
     * @throws Exception
     */
    private function assertExists(int $id): void
    {
        $exists = $this->connection->fetchOne(
            'SELECT 1 FROM promo_codes WHERE id = :id LIMIT 1',
            ['id' => $id],
        );

        if ($exists === false) {
            throw new DomainExceptionModule('order', 'error.promo_code_not_found', 35, status: 404);
        }
    }

    private function nullableDate(mixed $value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            throw new DomainExceptionModule('order', 'error.promo_code_date_invalid', 36, status: 422);
        }

        return gmdate('Y-m-d H:i:s', $timestamp);
    }

    private function nullablePositiveInt(mixed $value): ?int
    {
        if ($value === null || trim((string)$value) === '') {
            return null;
        }

        $limit = filter_var($value, FILTER_VALIDATE_INT);
        if ($limit === false || $limit <= 0) {
            throw new DomainExceptionModule('order', 'error.promo_code_usage_limit_invalid', 37, status: 422);
        }

        return $limit;
    }
}
