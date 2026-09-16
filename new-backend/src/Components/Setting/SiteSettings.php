<?php

declare(strict_types=1);

namespace App\Components\Setting;

use App\Modules\Program\Service\ProgramService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final class SiteSettings
{
    private const array DEFAULTS = [
        'cart_enabled'                       => false,
        'registration_enabled'               => false,
        'referral_enabled'                   => false,
        'withdrawals_enabled'                => false,
        'favorites_enabled'                  => true,
        'order_bonus_enabled'                => true,
        'order_bonus_percent'                => 1,
        'order_bonus_spend_limit_percent'    => 30,
        'welcome_bonus_enabled'              => false,
        'welcome_bonus_amount'               => 0,
        'promo_codes_enabled'                => false,
        'free_delivery_threshold'            => 3000,
        'cdek_delivery_price'                => 350,
        'post_delivery_price'                => 250,
        'order_emails_enabled'               => false,
        'home_features_enabled'              => true,
        'home_catalog_enabled'               => true,
        'home_video_enabled'                 => true,
        'home_blog_enabled'                  => true,
        'home_about_enabled'                 => true,
        'home_marketplaces_enabled'          => true,
        'home_certificates_enabled'          => true,
        'home_loyalty_enabled'               => true,
        'home_reviews_enabled'               => true,
        'home_contacts_enabled'              => true,
        'yandex_metrika_enabled'             => false,
        'yandex_metrika_id'                  => '',
        'bitrix_widget_enabled'              => false,
        'bitrix_widget_code'                 => '',
        'bitrix_crm_enabled'                 => false,
        'referral_percent'                   => 1,
        'seo_product_title_template'         => '{name} — купить натуральный продукт БИОФАРМ',
        'seo_product_description_template'   => '{name}: описание, состав, цена и сертификаты качества. Натуральная продукция БИОФАРМ с доставкой по России.',
        'seo_category_title_template'        => '{h1} — БИОФАРМ',
        'seo_category_description_template'  => 'Каталог продукции БИОФАРМ в категории {name}. Натуральные растительные экстракты и БАДы с доставкой по России.',
        'seo_attribute_title_template'       => '{h1} — БИОФАРМ',
        'seo_attribute_description_template' => '{h1}: натуральная продукция БИОФАРМ с понятным составом и доставкой по России.',
        'site_name'                          => 'БИОФАРМ',
        'site_phone'                         => '+7 (999) 123-45-67',
        'site_email'                         => 'bio.active@bk.ru',
        'site_logo_url'                      => '/uploads/images/logo.png',
        'site_default_og_image'              => '/assets/images/og/default.jpg',
        'site_address_country'               => 'RU',
        'site_address_region'                => 'Томская область',
        'site_address_locality'              => 'Томск',
        'site_address_street'                => 'особая экономическая зона микрорайон Академгородок, проспект Развитие 3Е',
        'robots_txt'                         => '',
        'robots_extra_disallow'              => '',
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

    /**
     * @return array<string, bool|float|int|string|null>
     */
    public static function defaults(): array
    {
        return self::DEFAULTS;
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::DEFAULTS);
    }

    /**
     * @return list<string>
     */
    public static function adminWritableKeys(): array
    {
        return array_values(array_filter(
            self::keys(),
            static fn (string $key): bool => !\in_array($key, ['bitrix_crm_enabled', 'welcome_bonus_enabled', 'welcome_bonus_amount', 'order_bonus_percent', 'referral_percent'], true),
        ));
    }

    public function bool(string $key, bool $default = false): bool
    {
        return match ($key) {
            'referral_enabled' => $this->rawBool('referral_enabled', $default)
                && $this->rawBool('cart_enabled'),
            'withdrawals_enabled' => $this->rawBool('withdrawals_enabled', $default)
                && $this->bool('referral_enabled'),
            'order_bonus_enabled',
            'promo_codes_enabled',
            'order_emails_enabled' => $this->rawBool($key, $default)
                && $this->rawBool('cart_enabled'),
            default => $this->rawBool($key, $default),
        };
    }

    public function rawBool(string $key, bool $default = false): bool
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

        // Public promises and checkout previews use the same rates as financial snapshots.
        $rules = new ProgramService($this->connection)->settings();
        $settings['order_bonus_percent'] = $rules['buyerBps'] / 100;
        $settings['referral_percent'] = $rules['levelsBps'][0] / 100;
        $settings['welcome_bonus_enabled'] = false;
        $settings['welcome_bonus_amount'] = 0;
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
