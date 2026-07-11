<?php

declare(strict_types=1);

namespace App\Components\Setting;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final class SiteSettings
{
    private const array DEFAULTS = [
        'referral_percent'     => 5,
        'registration_enabled' => false,
        'cart_enabled'         => false,
        'order_bonus_enabled'  => true,
        'order_bonus_percent'  => 5,
    ];

    /** @var array<string, bool|float|int|string|null>|null */
    private ?array $settings = null;

    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function get(string $key, bool|float|int|string|null $default = null): bool|float|int|string|null
    {
        $settings = $this->all();

        return \array_key_exists($key, $settings) ? $settings[$key] : $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);

        if (\is_bool($value)) {
            return $value;
        }

        if (\is_int($value) || \is_float($value)) {
            return $value !== 0;
        }

        if (\is_string($value)) {
            return \in_array(mb_strtolower($value), ['1', 'true', 'yes', 'on'], true);
        }

        return $default;
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $this->get($key, $default);

        if (\is_int($value)) {
            return $value;
        }

        if (\is_float($value)) {
            return (int)$value;
        }

        if (\is_string($value) && is_numeric($value)) {
            return (int)$value;
        }

        return $default;
    }

    /**
     * @return array<string, bool|float|int|string|null>
     */
    public function all(): array
    {
        if ($this->settings !== null) {
            return $this->settings;
        }

        $settings = self::DEFAULTS;

        try {
            $rows = $this->connection->fetchAllAssociative('SELECT `key`, value FROM site_settings');
        } catch (Exception) {
            $this->settings = $settings;

            return $settings;
        }

        foreach ($rows as $row) {
            $key = (string)$row['key'];
            $settings[$key] = self::normalize(json_decode((string)$row['value'], true));
        }

        $this->settings = $settings;

        return $settings;
    }

    private static function normalize(mixed $decoded): bool|float|int|string|null
    {
        if (\is_array($decoded) && \array_key_exists('value', $decoded)) {
            $decoded = $decoded['value'];
        }

        return \is_scalar($decoded) || $decoded === null ? $decoded : null;
    }
}
