<?php

declare(strict_types=1);

namespace App\Modules\Ozon;

use App\Components\Integration\Credential\IntegrationCredentialStore;
use Doctrine\DBAL\Connection;

use function App\Components\env_bool;

final readonly class OzonConnection
{
    private const string CREDENTIAL = 'ozon.seller.oauth';
    private const array DELIVERY_SCOPES = ['seller-api.ozon-logistics', 'seller-api.posting-fbo', 'seller-api.returns'];

    public function __construct(
        private Connection $db,
        private IntegrationCredentialStore $credentials,
        private OzonApi $api,
        private string $clientId,
        private string $clientSecret,
        private string $sellerId,
        public string $redirectUri,
    ) {}

    public function configured(): bool
    {
        $uri = parse_url($this->redirectUri);
        return $this->clientId !== '' && $this->clientSecret !== ''
            && OzonProduct::positiveId($this->sellerId) !== null
            && ($uri['path'] ?? null) === '/ozon/callback'
            && !empty($uri['host']) && !isset($uri['user']) && !isset($uri['pass'])
            && !isset($uri['query']) && !isset($uri['fragment'])
            && (($uri['scheme'] ?? null) === 'https'
                || (($uri['scheme'] ?? null) === 'http' && in_array($uri['host'] ?? '', ['localhost', '127.0.0.1'], true)));
    }

    public function connected(): bool
    {
        return $this->configured() && $this->savedTokens() !== null;
    }

    public function deliveryConnected(): bool
    {
        if (!$this->connected()) {
            return false;
        }
        $claims = self::claims($this->savedTokens()['access']);
        return is_array($claims['scope'] ?? null) && array_diff(self::DELIVERY_SCOPES, $claims['scope']) === [];
    }

    public function authorizeUrl(): string
    {
        $this->requireConfig();
        $state = bin2hex(random_bytes(32));
        $this->db->update('ozon_catalog_state', [
            'oauth_state' => hash('sha256', $state),
            'oauth_expires_at' => gmdate('Y-m-d H:i:s', time() + 600),
        ], ['id' => 1]);

        return 'https://seller.ozon.ru/app/appstore/oauth/authorize?' . http_build_query([
            'response_type' => 'code',
            'access_type' => 'offline',
            'state' => $state,
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => implode(' ', env_bool('OZON_DELIVERY_ENABLED', false)
                ? ['seller-api.product-read-only', ...self::DELIVERY_SCOPES] : ['seller-api.product-read-only']),
            'prompt' => 'select_company',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function complete(string $state, string $code): void
    {
        $this->requireConfig();
        if (!preg_match('/^[a-f0-9]{64}$/D', $state) || $code === '' || strlen($code) > 2048) {
            throw new OzonException('Ozon не подтвердил подключение. Запустите его заново из админки.');
        }
        $consumed = $this->db->executeStatement(
            'UPDATE ozon_catalog_state SET oauth_state = NULL, oauth_expires_at = NULL
             WHERE id = 1 AND oauth_state = ? AND oauth_expires_at >= UTC_TIMESTAMP()',
            [hash('sha256', $state)],
        );
        if ($consumed !== 1) {
            throw new OzonException('Ссылка подключения Ozon истекла. Запустите подключение заново.');
        }
        $this->withTokenLock(fn () => $this->exchange([
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
        ]));
    }

    public function token(): string
    {
        $this->requireConfig();

        return $this->withTokenLock(function (): string {
            $saved = $this->savedTokens();
            if ($saved === null) {
                throw new OzonException('Сначала подключите Ozon в настройках интеграций.');
            }
            if ($saved['expires'] > time() + 120) {
                return $saved['access'];
            }
            try {
                return $this->exchange(['grant_type' => 'refresh_token', 'refresh_token' => $saved['refresh']]);
            } catch (OzonException $exception) {
                if ($exception->getCode() === 401) {
                    $this->db->delete('integration_credentials', ['key' => self::CREDENTIAL]);
                }
                throw $exception;
            }
        });
    }

    private function savedTokens(): ?array
    {
        $tokens = json_decode($this->credentials->get(self::CREDENTIAL) ?? 'null', true);
        if (!is_array($tokens) || ($tokens['seller'] ?? null) !== $this->sellerId
            || ($tokens['client'] ?? null) !== $this->clientId || empty($tokens['refresh'])) {
            return null;
        }
        return $tokens;
    }

    private function exchange(array $parameters): string
    {
        $reply = $this->api->oauth($parameters + [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);
        $access = $reply['access_token'] ?? null;
        $refresh = $reply['refresh_token'] ?? null;
        $expires = filter_var($reply['expires_in'] ?? null, FILTER_VALIDATE_INT);
        if (!is_string($access) || !is_string($refresh) || $refresh === '' || $expires === false || $expires < 1) {
            throw new OzonException('Ozon не выдал токены для автоматической сверки.');
        }

        // Ozon uses the seller ID in the JWT subject for the authorization code grant.
        $claims = self::claims($access);
        if ((string) ($claims['sub'] ?? '') !== $this->sellerId) {
            throw new OzonException('В Ozon выбран другой магазин.');
        }
        $this->credentials->save(self::CREDENTIAL, json_encode([
            'seller' => $this->sellerId,
            'client' => $this->clientId,
            'access' => $access,
            'refresh' => $refresh,
            'expires' => time() + $expires,
        ], JSON_THROW_ON_ERROR));

        return $access;
    }

    private static function claims(string $access): array
    {
        $encoded = explode('.', $access)[1] ?? '';
        $claims = json_decode(base64_decode(strtr($encoded, '-_', '+/'), true) ?: '', true);
        return is_array($claims) ? $claims : [];
    }

    private function requireConfig(): void
    {
        if (!$this->configured()) {
            throw new OzonException('Укажите параметры Ozon Seller на сервере.');
        }
    }

    private function withTokenLock(callable $operation): string
    {
        $lock = 'ozon-oauth-' . sha1((string) $this->db->getDatabase());
        if ((int) $this->db->fetchOne('SELECT GET_LOCK(?, 3)', [$lock]) !== 1) {
            throw new OzonException('Подключение Ozon обновляется. Повторите попытку.');
        }
        try {
            return $operation();
        } finally {
            $this->db->fetchOne('SELECT RELEASE_LOCK(?)', [$lock]);
        }
    }
}
