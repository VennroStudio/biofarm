<?php

declare(strict_types=1);

namespace App\Modules\User\Service\Phone;

use App\Components\Exception\DomainExceptionModule;
use Doctrine\DBAL\Connection;

final readonly class PhoneChallenges
{
    private const int LIFETIME = 300;
    private const int MAX_ATTEMPTS = 5;

    public function __construct(private Connection $db, private SmsAero $sms) {}

    public function start(string $phone, ?int $userId, string $origin, callable $recordRequest, bool $registration = false): array
    {
        if (!$this->sms->configured()) {
            throw new DomainExceptionModule('phone', SmsAero::UNAVAILABLE_MESSAGE, 73, status: 503);
        }
        $token = bin2hex(random_bytes(32));
        $id = $this->challengeId($token, $registration);
        $now = time();
        $originHash = hash('sha256', $origin);
        $this->db->transactional(function () use ($id, $phone, $userId, $originHash, $now, $recordRequest): void {
            $this->db->executeStatement("INSERT INTO program_locks (id,payload) VALUES ('phone-verification','{}')
                ON DUPLICATE KEY UPDATE id=VALUES(id)");
            $this->db->executeStatement('DELETE FROM phone_verification_challenges WHERE expires_at < ?', [$now - 86400]);
            $latest = $this->db->fetchOne(
                'SELECT MAX(created_at) FROM phone_verification_challenges WHERE phone = ? AND sandbox = 0',
                [$phone],
            );
            $recentPhone = $this->db->fetchOne(
                'SELECT COUNT(*) FROM phone_verification_challenges WHERE phone = ? AND sandbox = 0 AND created_at > ?',
                [$phone, $now - 3600],
            );
            $recentOrigin = $this->db->fetchOne(
                'SELECT COUNT(*) FROM phone_verification_challenges WHERE origin_hash = ? AND sandbox = 0 AND created_at > ?',
                [$originHash, $now - 3600],
            );
            if (($latest !== null && (int)$latest > $now - 60) || (int)$recentPhone >= 5 || (int)$recentOrigin >= 25) {
                throw new DomainExceptionModule('phone', 'Слишком много запросов. Подождите перед повторной отправкой.', 71, status: 429);
            }

            $this->db->insert('phone_verification_challenges', [
                'id' => $id, 'phone' => $phone, 'user_id' => $userId, 'origin_hash' => $originHash,
                'sandbox' => 0, 'created_at' => $now, 'expires_at' => $now + self::LIFETIME,
            ]);
            $recordRequest($id);
        });

        $session = $this->sms->start($phone);
        if ($session['number'] !== $phone) {
            throw new DomainExceptionModule('phone', 'Не удалось подтвердить этот номер.', 70, status: 422);
        }

        $this->db->update('phone_verification_challenges', [
            'provider_id' => $session['id'], 'state' => $session['status'],
        ], ['id' => $id]);

        return ['challenge' => $token, 'state' => $this->stateName($session['status']), 'resendAfter' => 60];
    }

    public function advance(string $token, ?int $userId, ?string $code, bool $registration = false): string
    {
        $result = $this->db->transactional(function () use ($token, $userId, $code, $registration): string|DomainExceptionModule {
            $row = $this->lock($token, $userId, $registration);
            $state = (int)$row['state'];
            if (in_array($state, [SmsAero::CONFIRMED, SmsAero::DECLINED, SmsAero::FAILED], true)) {
                return $this->stateName($state);
            }

            $now = time();
            if ($code === null && (int)$row['checked_at'] > $now - 3) {
                return $this->stateName($state);
            }

            if ($code !== null && ($state !== SmsAero::CODE_REQUIRED || (int)$row['attempts'] >= self::MAX_ATTEMPTS)) {
                return new DomainExceptionModule('phone', 'Запросите новый код подтверждения.', 72, status: 422);
            }

            $values = ['checked_at' => $now, 'attempts' => (int)$row['attempts'] + (int)($code !== null)];
            $this->db->update('phone_verification_challenges', $values, ['id' => $row['id']]);
            try {
                $state = $code === null
                    ? $this->sms->status((int)$row['provider_id'], $row['phone'])
                    : $this->sms->verify((int)$row['provider_id'], $row['phone'], $code);
            } catch (DomainExceptionModule $exception) {
                return $exception;
            }

            $this->db->update('phone_verification_challenges', ['state' => $state], ['id' => $row['id']]);

            return $this->stateName($state);
        });

        if ($result instanceof DomainExceptionModule) {
            throw $result;
        }

        return $result;
    }

    public function consume(string $token, ?int $userId, callable $complete, bool $registration = false): mixed
    {
        return $this->db->transactional(function () use ($token, $userId, $complete, $registration): mixed {
            $row = $this->lock($token, $userId, $registration);
            if ((int)$row['state'] !== SmsAero::CONFIRMED) {
                throw new DomainExceptionModule('phone', 'Сначала подтвердите номер телефона.', 70, status: 422);
            }

            $result = $complete($row['phone'], $row['id']);
            $this->db->update('phone_verification_challenges', ['consumed_at' => time()], ['id' => $row['id']]);

            return $result;
        });
    }

    private function lock(string $token, ?int $userId, bool $registration): array
    {
        $row = $this->db->fetchAssociative(
            'SELECT * FROM phone_verification_challenges WHERE id = ? FOR UPDATE',
            [$this->challengeId($token, $registration)],
        );
        if (!$row || $row['provider_id'] === null || $row['consumed_at'] !== null || $row['expires_at'] <= time()
            || (bool)$row['sandbox']
            || ($row['user_id'] === null ? null : (int)$row['user_id']) !== $userId) {
            throw new DomainExceptionModule('phone', 'Подтверждение истекло. Запросите новый код.', 74, status: 422);
        }

        return $row;
    }

    private function challengeId(string $token, bool $registration): string
    {
        return hash('sha256', $registration ? 'registration:' . $token : $token);
    }

    private function stateName(int $state): string
    {
        return match ($state) {
            SmsAero::CONFIRMED => 'confirmed',
            SmsAero::CODE_REQUIRED => 'code',
            SmsAero::FAILED, SmsAero::DECLINED => 'failed',
            default => 'pending',
        };
    }
}
