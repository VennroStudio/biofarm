<?php

declare(strict_types=1);

namespace App\Modules\User\Service\Phone;

use App\Components\Http\Cookie\CookieManager;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use App\Modules\User\Entity\User\Fields\Enums\UserStatus;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function App\Components\env;

final readonly class PhoneLoginReceipt
{
    private const string COOKIE = 'phone_login_consent';
    private const int TTL = 180 * 86400;

    public function __construct(private Connection $db, private CookieManager $cookies) {}

    public function remember(ResponseInterface $response, int $userId, string $phone): ResponseInterface
    {
        $data = $userId . '.' . (time() + self::TTL);

        return $this->cookies->set($response, self::COOKIE, $data . '.' . $this->signature($data, $phone), self::TTL, hostOnly: true);
    }

    public function forget(ResponseInterface $response): ResponseInterface
    {
        return $this->cookies->delete($response, self::COOKIE, hostOnly: true);
    }

    public function rememberedUser(ServerRequestInterface $request, string $phone): ?int
    {
        $cookie = $request->getCookieParams()[self::COOKIE] ?? '';
        if (!is_string($cookie) || !preg_match('/^(\d{1,10})\.(\d{10})\.([a-f0-9]{64})$/D', $cookie, $parts)
            || (int)$parts[2] <= time() || !hash_equals($this->signature($parts[1] . '.' . $parts[2], $phone), $parts[3])) {
            return null;
        }

        $id = (int)$parts[1];
        $savedPhone = $this->db->fetchOne(
            'SELECT p.phone FROM user_profiles p JOIN users u ON u.id = p.user_id
             WHERE u.id = ? AND u.deleted_at IS NULL AND u.status = ? AND u.role = ?
               AND p.verified_phone = ? AND p.phone_verified_at IS NOT NULL AND p.phone_verification_sandbox = 0',
            [$id, UserStatus::ACTIVE->value, UserRole::USER->value, $phone],
        );

        return is_string($savedPhone) && PhoneNumber::normalize($savedPhone) === $phone ? $id : null;
    }

    private function signature(string $data, string $phone): string
    {
        return hash_hmac('sha256', 'phone-login-consent:' . $data . ':' . $phone, env('JWT_SECRET'));
    }
}
