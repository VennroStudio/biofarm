<?php

declare(strict_types=1);
namespace App\Components\Privacy;

use App\Components\Exception\DomainExceptionModule;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ConsentRecorder
{
    public const string VERSION = '2026-10-04';
    public function __construct(private Connection $db) {}

    public function assertForm(array $payload, bool $offer = false): void
    {
        if (!\in_array($payload['personalDataConsent'] ?? null, [true, '1'], true)
            || ($payload['consentVersion'] ?? null) !== self::VERSION) {
            throw new DomainExceptionModule('consent', 'Подтвердите согласие на обработку персональных данных.', 1, status: 422);
        }
        if ($offer && ($payload['offerAccepted'] ?? null) !== true) {
            throw new DomainExceptionModule('consent', 'Примите условия публичной оферты.', 2, status: 422);
        }
        if (isset($payload['marketingEmailConsent']) && !\in_array($payload['marketingEmailConsent'], [true, false, '1', '0'], true)) {
            throw new DomainExceptionModule('consent', 'Некорректное согласие на рекламу.', 3, status: 422);
        }
    }

    public function recordForm(ServerRequestInterface $request, array $payload, string $subjectType, string $subjectId): void
    {
        $this->assertForm($payload, $subjectType === 'order');
        $choices = ['personal_data' => true, 'marketing_email' => \in_array($payload['marketingEmailConsent'] ?? false, [true, '1'], true)];
        if ($subjectType === 'order') $choices['offer'] = true;
        $this->db->transactional(function () use ($request, $choices, $subjectType, $subjectId): void {
            foreach ($choices as $kind => $accepted) $this->record($request, $subjectType, $subjectId, $kind, $accepted);
        });
    }

    public function record(ServerRequestInterface $request, string $subjectType, string $subjectId, string $kind, bool $accepted): void
    {
        $slug = match ($kind) {
            'personal_data' => 'personal-data-consent',
            'marketing_email' => 'marketing-consent',
            'offer' => 'oferta',
            default => 'cookie-policy',
        };
        $document = dirname(__DIR__, 3) . '/templates/pages/legal/documents/' . $slug . '.html.twig';
        $digest = hash_file('sha256', $document);
        $this->db->insert('privacy_consents', [
            'id'=>bin2hex(random_bytes(16)), 'subject_type'=>$subjectType, 'subject_id'=>$subjectId,
            'kind'=>$kind, 'version'=>self::VERSION, 'accepted'=>(int)$accepted, 'created_at'=>gmdate('Y-m-d H:i:s'),
            // Deliberately do not store request bodies, credentials, IPs or contact details here.
            'evidence'=>json_encode(['path'=>$request->getUri()->getPath(), 'action'=>'explicit_choice', 'document'=>'/' . $slug, 'documentSha256'=>$digest], JSON_THROW_ON_ERROR),
        ]);
    }
}
