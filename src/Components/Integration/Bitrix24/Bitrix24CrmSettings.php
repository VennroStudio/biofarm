<?php

declare(strict_types=1);

namespace App\Components\Integration\Bitrix24;

use App\Components\Integration\Credential\IntegrationCredentialStore;
use App\Components\Setting\SiteSettings;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use JsonException;

final readonly class Bitrix24CrmSettings
{
    public const string WEBHOOK_KEY = 'bitrix24_incoming_webhook_url';

    public function __construct(
        private SiteSettings $settings,
        private IntegrationCredentialStore $credentials,
        private Connection $connection,
    ) {}

    public function enabled(): bool
    {
        try {
            $stored = $this->connection->fetchOne(
                "SELECT value FROM site_settings WHERE `key` = 'bitrix_crm_enabled' LIMIT 1"
            );
        } catch (Exception) {
            return $this->settings->rawBool('bitrix_crm_enabled');
        }

        if (!\is_string($stored) || $stored === '') {
            return $this->settings->rawBool('bitrix_crm_enabled');
        }

        try {
            $decoded = json_decode($stored, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->settings->rawBool('bitrix_crm_enabled');
        }

        return \is_array($decoded) && (bool)($decoded['value'] ?? false);
    }

    public function webhookUrl(): ?string
    {
        $value = $this->credentials->get(self::WEBHOOK_KEY);

        return $value !== null && $value !== '' ? $value : null;
    }

    public function ready(): bool
    {
        return $this->enabled() && $this->webhookUrl() !== null;
    }

    /**
     * @throws Exception
     * @throws JsonException
     */
    public function update(bool $enabled, ?string $webhookUrl): void
    {
        $this->connection->executeStatement(
            'INSERT INTO site_settings (`key`, value) VALUES (:key, :value)
             ON DUPLICATE KEY UPDATE value = VALUES(value)',
            [
                'key'   => 'bitrix_crm_enabled',
                'value' => json_encode(['value' => $enabled], JSON_THROW_ON_ERROR),
            ],
        );

        if ($webhookUrl !== null && $webhookUrl !== '') {
            $this->credentials->save(self::WEBHOOK_KEY, $webhookUrl);
        }
    }

    /**
     * @return array{enabled: bool, has_webhook: bool, webhook_mask: string|null}
     */
    public function publicState(): array
    {
        $webhook = $this->webhookUrl();

        return [
            'enabled'      => $this->enabled(),
            'has_webhook'  => $webhook !== null,
            'webhook_mask' => $webhook !== null ? $this->mask($webhook) : null,
        ];
    }

    private function mask(string $url): string
    {
        $parts = parse_url($url);
        $host = \is_array($parts) && isset($parts['host']) ? (string)$parts['host'] : 'bitrix24';

        return 'https://' . $host . '/rest/...';
    }
}
