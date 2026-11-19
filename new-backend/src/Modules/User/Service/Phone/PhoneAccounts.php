<?php

declare(strict_types=1);

namespace App\Modules\User\Service\Phone;

use App\Components\Exception\DomainExceptionModule;
use App\Components\Http\Middleware\Identity\Identity;
use App\Components\Setting\SiteSettings;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use App\Modules\User\Entity\User\Fields\Enums\UserStatus;
use Doctrine\DBAL\Connection;

use function App\Components\env_bool;

final readonly class PhoneAccounts
{
    public function __construct(private Connection $db, private SiteSettings $settings) {}

    public function checkoutEnabled(): bool
    {
        return env_bool('CHECKOUT_PHONE_VERIFICATION_ENABLED', false);
    }

    public function checkoutPhone(?Identity $identity, string $input, bool $lock = false): string
    {
        $phone = PhoneNumber::normalize($input);
        $profile = $identity === null || $phone === null ? false : $this->db->fetchAssociative(
            'SELECT phone, phone_verification_sandbox FROM user_profiles
             WHERE user_id = ? AND verified_phone = ? AND phone_verified_at IS NOT NULL' . ($lock ? ' FOR UPDATE' : ''),
            [$identity->id, $phone],
        );
        if (!$profile || PhoneNumber::normalize($profile['phone']) !== $phone
            || (bool)$profile['phone_verification_sandbox']) {
            throw new DomainExceptionModule('phone', 'Сначала подтвердите номер телефона.', 70, status: 422);
        }

        return $phone;
    }

    /** Called while the verified challenge is locked, so a failed account update leaves the challenge reusable. */
    public function account(string $phone, ?Identity $identity): ?array
    {
        $owner = $this->db->fetchAssociative(
            'SELECT u.id, u.first_name, u.role, u.status, u.email, u.deleted_at, p.phone, p.phone_verification_sandbox
             FROM user_profiles p JOIN users u ON u.id = p.user_id
             WHERE p.verified_phone = ? AND p.phone_verified_at IS NOT NULL FOR UPDATE',
            [$phone],
        );
        if ($owner && ($identity === null || (int)$owner['id'] !== $identity->id)) {
            if ($identity !== null) {
                throw $this->conflict('Этот номер уже привязан к другому аккаунту.');
            }
            if ((bool)$owner['phone_verification_sandbox']
                || PhoneNumber::normalize($owner['phone']) !== $phone
                || (int)$owner['role'] !== UserRole::USER->value || (int)$owner['status'] !== UserStatus::ACTIVE->value
                || $owner['deleted_at'] !== null) {
                throw $this->conflict('Войдите в свой аккаунт по email и паролю.');
            }

            return $owner;
        }

        if ($identity !== null) {
            $this->bind($identity->id, $phone);

            return null;
        }

        $legacyAccount = $this->db->fetchOne(
            "SELECT p.user_id FROM user_profiles p JOIN users u ON u.id = p.user_id
             WHERE u.deleted_at IS NULL AND p.phone IS NOT NULL
               AND REGEXP_REPLACE(p.phone, '[^0-9]', '') IN (?, ?, ?) LIMIT 1",
            [$phone, '8' . substr($phone, 1), substr($phone, 1)],
        );
        if ($legacyAccount !== false) {
            throw $this->conflict('Номер уже указан в аккаунте. Войдите по email и паролю, чтобы подтвердить его.');
        }

        return null;
    }

    public function bind(int $userId, string $phone): void
    {
        $profile = $this->db->fetchAssociative('SELECT * FROM user_profiles WHERE user_id = ? FOR UPDATE', [$userId]);
        $values = [
            'phone' => '+' . $phone, 'verified_phone' => $phone, 'phone_verified_at' => gmdate('Y-m-d H:i:s'),
            'phone_verification_sandbox' => 0,
        ];
        if (!$profile) {
            $this->db->insert('user_profiles', $values + [
                'user_id' => $userId, 'referral_code' => 'bf-' . $userId, 'created_at' => gmdate('Y-m-d H:i:s'),
            ]);

            return;
        }
        if ($profile['verified_phone'] !== $phone || (bool)$profile['phone_verification_sandbox']) {
            $values += ['ozon_phone_checked_at' => null, 'ozon_phone_available' => null];
        }
        $this->db->update('user_profiles', $values, ['user_id' => $userId]);
    }

    public function allowRegistration(): void
    {
        if (!$this->settings->bool('registration_enabled')) {
            throw new DomainExceptionModule('phone', 'Регистрация сейчас недоступна.', 76, status: 403);
        }
    }

    private function conflict(string $message): DomainExceptionModule
    {
        return new DomainExceptionModule('phone', $message, 75, status: 409);
    }
}
