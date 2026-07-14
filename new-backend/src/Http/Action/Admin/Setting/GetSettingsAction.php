<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Setting;

use App\Components\Http\Response\JsonDataResponse;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class GetSettingsAction implements RequestHandlerInterface
{
    private const array DEFAULTS = [
        'referral_percent'    => 5,
        'registration_enabled' => false,
        'cart_enabled'         => false,
        'order_bonus_enabled' => true,
        'order_bonus_percent' => 5,
        'site_name'            => 'БИОФАРМ',
        'site_phone'           => '+7 (999) 123-45-67',
        'site_email'           => 'bio.active@bk.ru',
        'site_logo_url'        => '/uploads/images/logo.png',
        'site_default_og_image' => '/assets/images/og/default.jpg',
        'site_address_country' => 'RU',
        'site_address_region'  => 'Томская область',
        'site_address_locality' => 'Томск',
        'site_address_street'  => 'особая экономическая зона микрорайон Академгородок, проспект Развитие 3Е',
        'robots_extra_disallow' => '',
    ];

    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $rows = $this->connection->fetchAllAssociative('SELECT `key`, value FROM site_settings');
        $settings = self::DEFAULTS;

        foreach ($rows as $row) {
            $settings[(string)$row['key']] = self::normalizeSettingValue(json_decode((string)$row['value'], true));
        }

        return new JsonDataResponse($settings);
    }

    private static function normalizeSettingValue(mixed $decoded): bool|float|int|string|null
    {
        if (\is_array($decoded) && \array_key_exists('value', $decoded)) {
            return self::normalizeScalar($decoded['value']);
        }

        return self::normalizeScalar($decoded);
    }

    private static function normalizeScalar(mixed $value): bool|float|int|string|null
    {
        return \is_scalar($value) || $value === null ? $value : null;
    }
}
