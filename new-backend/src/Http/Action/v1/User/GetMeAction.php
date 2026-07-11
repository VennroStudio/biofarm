<?php

declare(strict_types=1);

namespace App\Http\Action\v1\User;

use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataResponse;
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
    ) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $identity = RequestIdentity::get($request);

        return new JsonDataResponse($this->user($identity->id));
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
                'u.avatar',
                'u.created_at',
                'up.phone',
                'up.card_number',
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

        return [
            'id'                  => (int)$row['id'],
            'role'                => (int)$row['role'],
            'status'              => (int)$row['status'],
            'first_name'          => (string)$row['first_name'],
            'last_name'           => (string)$row['last_name'],
            'name'                => trim((string)$row['first_name'] . ' ' . (string)$row['last_name']),
            'email'               => (string)$row['email'],
            'avatar'              => $row['avatar'],
            'phone'               => $row['phone'],
            'card_number'         => $row['card_number'],
            'bonus_balance'       => (int)$row['bonus_balance'],
            'is_partner'          => (bool)(int)$row['is_partner'],
            'referral_code'       => $row['referral_code'],
            'referred_by_user_id' => $row['referred_by_user_id'] !== null ? (int)$row['referred_by_user_id'] : null,
            'created_at'          => (string)$row['created_at'],
        ];
    }
}
