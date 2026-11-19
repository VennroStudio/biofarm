<?php

declare(strict_types=1);

namespace App\Http\Action\v1\User;

use App\Components\Exception\DomainExceptionModule;
use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Privacy\ConsentRecorder;
use App\Modules\User\Entity\User\Fields\Enums\UserStatus;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class UpdatePrivacyPreferencesAction implements RequestHandlerInterface
{
    public function __construct(
        private Connection $db,
        private ConsentRecorder $consents,
        private GetMeAction $profile,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $identity = RequestIdentity::get($request);
        $body = (array)$request->getParsedBody();
        $marketing = $body['marketingEmailSubscribed'] ?? null;
        $revokePersonalData = ($body['revokePersonalDataConsent'] ?? null) === true;
        if (is_bool($marketing) === $revokePersonalData) {
            throw new DomainExceptionModule('consent', 'Укажите одно изменение настроек.', 5, status: 422);
        }

        $this->db->transactional(function () use ($request, $identity, $marketing, $body, $revokePersonalData): void {
            $user = $this->db->fetchAssociative(
                'SELECT email, email_confirmation_required, status FROM users WHERE id = ? AND deleted_at IS NULL FOR UPDATE',
                [$identity->id],
            );
            if ($user === false) {
                throw new DomainExceptionModule('consent', 'Аккаунт не найден.', 5, status: 404);
            }
            if ($revokePersonalData) {
                $this->consents->record($request, 'user', (string)$identity->id, 'personal_data', false, ['action' => 'revoke']);
                return;
            }

            if ($marketing && (($body['marketingVersion'] ?? null) !== ConsentRecorder::MARKETING_VERSION
                || ($body['email'] ?? null) !== $user['email']
                || (bool)$user['email_confirmation_required'] || (int)$user['status'] !== UserStatus::ACTIVE->value)) {
                throw new DomainExceptionModule('consent', 'Для подписки нужен подтверждённый email. Проверьте адрес и обновите страницу.', 5, status: 422);
            }
            $this->consents->recordMarketing($request, $identity->id, $user['email'], $marketing);
        });

        return $this->profile->handle($request);
    }
}
