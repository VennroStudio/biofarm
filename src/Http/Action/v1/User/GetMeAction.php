<?php

declare(strict_types=1);

namespace App\Http\Action\v1\User;

use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\Privacy\ConsentRecorder;
use App\Modules\Program\Service\ProgramService;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use App\Modules\User\Entity\User\Fields\Enums\UserStatus;
use App\Modules\User\Service\Phone\PhoneLoginReceipt;
use App\Modules\User\Service\Phone\PhoneNumber;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class GetMeAction implements RequestHandlerInterface
{
    public function __construct(
        private Connection $connection,
        private ProgramService $program,
        private ConsentRecorder $consents,
        private PhoneLoginReceipt $phoneLoginReceipt,
    ) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $identity = RequestIdentity::get($request);
        $this->program->dashboard($identity->id);

        $user = $this->user($identity->id);
        $response = (new JsonDataResponse($user))->withHeader('Cache-Control', 'private, no-store');
        if ($identity->role !== UserRole::USER) {
            return $response;
        }

        $phone = PhoneNumber::normalize($user['phone'] ?? null);
        if ($phone !== null && ($user['phone_verified_at'] ?? null) && $user['personal_data_consent_valid']) {
            return $this->phoneLoginReceipt->remember($response, $identity->id, $phone);
        }

        return $this->phoneLoginReceipt->forget($response);
    }

    /**
     * @return array<string, bool|int|string|null>
     * @throws Exception
     */
    private function user(int $userId): array
    {
        $row = $this->connection->createQueryBuilder()
            ->select(
                'u.id',
                'u.role',
                'u.status',
                'u.first_name',
                'u.last_name',
                'u.email',
                'u.email_confirmation_required',
                'u.avatar',
                'u.created_at',
                'up.phone',
                'up.verified_phone',
                'up.phone_verified_at',
                'up.phone_verification_sandbox',
                'up.ozon_phone_checked_at',
                'up.ozon_phone_available',
                'COALESCE(up.bonus_balance, 0) AS bonus_balance',
                'COALESCE(up.is_partner, 0) AS is_partner',
                'up.referral_code',
                'up.referred_by_user_id',
            )
            ->from('users', 'u')
            ->leftJoin('u', 'user_profiles', 'up', 'up.user_id = u.id')
            ->where('u.id = :id')
            ->andWhere('u.deleted_at IS NULL')
            ->setParameter('id', $userId)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if ($row === false) {
            return [];
        }

        $verified = $row['phone_verified_at'] !== null
            && PhoneNumber::normalize($row['phone']) === $row['verified_phone']
            && !(bool)$row['phone_verification_sandbox'];

        return [
            'id'                     => (int)$row['id'],
            'role'                   => (int)$row['role'],
            'status'                 => (int)$row['status'],
            'first_name'             => (string)$row['first_name'],
            'last_name'              => (string)$row['last_name'],
            'name'                   => trim((string)$row['first_name'] . ' ' . (string)$row['last_name']),
            'email'                  => (string)$row['email'],
            'email_verified'         => (int)$row['status'] === UserStatus::ACTIVE->value && !(bool)$row['email_confirmation_required'],
            'marketing_email_subscribed' => $this->consents->marketingEmailSubscribed($userId, (string)$row['email']),
            'personal_data_consent_valid' => $this->consents->personalDataIsCurrent($userId),
            'avatar'                 => $row['avatar'],
            'phone'                  => $row['phone'],
            'phone_verified_at'      => $verified ? $row['phone_verified_at'] : null,
            'ozon_phone_checked_at' => $verified ? $row['ozon_phone_checked_at'] : null,
            'ozon_phone_available'  => $verified && $row['ozon_phone_available'] !== null ? (bool)$row['ozon_phone_available'] : null,
            'bonus_balance'          => (int)$row['bonus_balance'],
            'is_partner'             => (bool)(int)$row['is_partner'],
            'referral_code'          => $row['referral_code'],
            'referred_by_user_id'    => $row['referred_by_user_id'] !== null ? (int)$row['referred_by_user_id'] : null,
            'created_at'             => (string)$row['created_at'],
        ];
    }
}
