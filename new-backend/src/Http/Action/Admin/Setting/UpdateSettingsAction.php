<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Setting;

use App\Components\Http\Response\JsonDataResponse;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use JsonException;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class UpdateSettingsAction implements RequestHandlerInterface
{
    private const array ALLOWED_KEYS = [
        'referral_percent',
        'registration_enabled',
        'cart_enabled',
        'order_bonus_enabled',
        'order_bonus_percent',
    ];

    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * @throws Exception
     * @throws JsonException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $payload = (array)$request->getParsedBody();
        /** @var array<string, bool|float|int|string|null> $updated */
        $updated = [];

        foreach (self::ALLOWED_KEYS as $key) {
            if (!\array_key_exists($key, $payload)) {
                continue;
            }

            $value = self::normalizePayloadValue($payload[$key]);
            $this->connection->executeStatement(
                'INSERT INTO site_settings (`key`, value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE value = VALUES(value)',
                [
                    'key'   => $key,
                    'value' => json_encode(['value' => $value], JSON_THROW_ON_ERROR),
                ],
            );
            $updated[$key] = $value;
        }

        return new JsonDataResponse($updated);
    }

    private static function normalizePayloadValue(mixed $value): bool|float|int|string|null
    {
        return \is_scalar($value) || $value === null ? $value : null;
    }
}
