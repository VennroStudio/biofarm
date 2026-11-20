<?php

declare(strict_types=1);

namespace App\Components\Privacy;

use App\Components\Exception\DomainExceptionModule;
use App\Modules\Page\Service\LegalDocuments;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ConsentRecorder
{
    public const string VERSION = '2026-11-16';
    public const string OFFER_VERSION = '2026-11-16';
    public const string MARKETING_VERSION = '2026-10-04';
    public const string COOKIE_VERSION = '2026-10-04';
    public const string OZON_TERMS = 'https://docs.ozon.ru/legal/terms-of-use/site/external-orders/';

    public function __construct(private Connection $db) {}

    public function assertForm(array $payload): void
    {
        if (!in_array($payload['personalDataConsent'] ?? null, [true, '1'], true)
            || ($payload['consentVersion'] ?? null) !== self::VERSION) {
            throw new DomainExceptionModule('consent', 'Подтвердите согласие на обработку персональных данных.', 1, status: 422);
        }
    }

    public function assertOffer(array $payload): void
    {
        if (($payload['offerAccepted'] ?? null) !== true || ($payload['offerVersion'] ?? null) !== self::OFFER_VERSION) {
            throw new DomainExceptionModule('consent', 'Обновите страницу и подтвердите заказ на условиях действующей оферты.', 2, status: 422);
        }
    }

    public function requirePersonalData(array $payload, ?int $userId, bool $lock = false): bool
    {
        if ($userId !== null && $this->personalDataIsCurrent($userId, $lock)) {
            return false;
        }

        $this->assertForm($payload);
        return true;
    }

    public function personalDataIsCurrent(int $userId, bool $lock = false): bool
    {
        if ($lock) {
            $this->db->fetchOne('SELECT id FROM users WHERE id = ? FOR UPDATE', [$userId]);
        }
        $row = $this->latestUserChoice($userId, 'personal_data', $lock);

        return $row !== false
            && (bool)$row['accepted']
            && $row['version'] === self::VERSION
            && ($this->evidence($row)['documentSha256'] ?? null) === $this->documentHash('personal-data-consent');
    }

    public function marketingEmailSubscribed(int $userId, string $email): bool
    {
        $row = $this->latestUserChoice($userId, 'marketing_email');

        return $row !== false && (bool)$row['accepted']
            && ($this->evidence($row)['emailSha256'] ?? null) === $this->emailDigest($email);
    }

    public function recordForm(ServerRequestInterface $request, array $payload, string $subjectType, string $subjectId): void
    {
        $this->assertForm($payload);
        $this->record($request, $subjectType, $subjectId, 'personal_data', true);
    }

    public function attachPhoneLoginConsent(ServerRequestInterface $request, int $userId, string $challengeId): void
    {
        if ($this->personalDataIsCurrent($userId, lock: true)) {
            return;
        }
        $acceptedAt = $this->phoneConsentAcceptedAt($challengeId);
        if ($acceptedAt === null) {
            return;
        }
        $latestChoice = $this->latestUserChoice($userId, 'personal_data', lock: true);
        if ($latestChoice !== false && $latestChoice['created_at'] >= $acceptedAt) {
            return;
        }
        $this->record($request, 'user', (string)$userId, 'personal_data', true, [
            'action' => 'phone_login_confirmed',
            'acceptedAt' => $acceptedAt,
            'challengeId' => $challengeId,
        ]);
    }

    public function requirePhoneRegistrationConsent(string $challengeId): string
    {
        return $this->phoneConsentAcceptedAt($challengeId)
            ?? throw new DomainExceptionModule('consent', 'Запросите новый код и подтвердите согласие на обработку персональных данных.', 1, status: 422);
    }

    private function phoneConsentAcceptedAt(string $challengeId): ?string
    {
        $proof = $this->db->fetchAssociative(
            "SELECT created_at, version, evidence FROM privacy_consents
             WHERE subject_type = 'phone_verification' AND subject_id = ? AND kind = 'personal_data' AND accepted = 1",
            [$challengeId],
        );
        if ($proof === false || $proof['version'] !== self::VERSION
            || ($this->evidence($proof)['documentSha256'] ?? null) !== $this->documentHash('personal-data-consent')) {
            return null;
        }

        return $proof['created_at'];
    }

    public function recordMarketing(ServerRequestInterface $request, int $userId, string $email, bool $accepted): void
    {
        $this->record($request, 'user', (string)$userId, 'marketing_email', $accepted, [
            'emailSha256' => $this->emailDigest($email),
        ]);
    }

    public function record(ServerRequestInterface $request, string $subjectType, string $subjectId, string $kind, bool $accepted, array $details = []): void
    {
        [$slug, $version] = match ($kind) {
            'personal_data', 'verification_sms' => ['personal-data-consent', self::VERSION],
            'marketing_email' => ['marketing-consent', self::MARKETING_VERSION],
            'offer' => ['oferta', self::OFFER_VERSION],
            'cookie_analytics', 'cookie_marketing' => ['cookie-policy', self::COOKIE_VERSION],
        };
        $this->db->insert('privacy_consents', [
            'id' => bin2hex(random_bytes(16)),
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'kind' => $kind,
            'version' => $version,
            'accepted' => (int)$accepted,
            'created_at' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.u'),
            'evidence' => json_encode($details + [
                'path' => $request->getUri()->getPath(),
                'action' => 'explicit_choice',
                'document' => '/' . $slug,
                'documentSha256' => $this->documentHash($slug),
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        ]);
    }

    private function latestUserChoice(int $userId, string $kind, bool $lock = false): array|false
    {
        return $this->db->fetchAssociative(
            "SELECT accepted, version, created_at, evidence FROM privacy_consents
             WHERE subject_type = 'user' AND subject_id = ? AND kind = ?
             ORDER BY created_at DESC, id DESC LIMIT 1" . ($lock ? ' FOR UPDATE' : ''),
            [(string)$userId, $kind],
        );
    }

    private function evidence(array $row): array
    {
        return json_decode($row['evidence'], true, flags: JSON_THROW_ON_ERROR);
    }

    private function documentHash(string $slug): string
    {
        return hash('sha256', (new LegalDocuments($this->db))->content($slug));
    }

    private function emailDigest(string $email): string
    {
        return hash('sha256', mb_strtolower(trim($email)));
    }
}
