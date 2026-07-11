<?php

declare(strict_types=1);

namespace App\Http\Action\v1\User;

use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\Setting\SiteSettings;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class GetReferralInfoAction implements RequestHandlerInterface
{
    public function __construct(
        private Connection $connection,
        private SiteSettings $settings,
    ) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $identity = RequestIdentity::get($request);
        $referralPercent = (int)$this->settings->get('referral_percent', 5);
        $profile = $this->profile($identity->id);
        $referralCode = $profile['referral_code'] !== null ? (string)$profile['referral_code'] : 'bf-' . $identity->id;

        $referredUsers = (int)$this->connection->fetchOne(
            'SELECT COUNT(user_id) FROM user_profiles WHERE referred_by_user_id = :userId',
            ['userId' => $identity->id],
        );

        $totalEarnings = (int)$this->connection->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) FROM bonus_transactions WHERE user_id = :userId AND type = 'referral_bonus'",
            ['userId' => $identity->id],
        );

        $pendingEarnings = (int)$this->connection->fetchOne(
            "SELECT COALESCE(SUM(FLOOR(total * :percent / 100)), 0)
             FROM orders
             WHERE referred_by IN (:idRef, :codeRef)
               AND payment_status <> 'completed'",
            [
                'percent' => $referralPercent,
                'idRef'   => (string)$identity->id,
                'codeRef' => $referralCode,
            ],
        );

        return new JsonDataResponse([
            'referred_users'   => $referredUsers,
            'total_earnings'   => $totalEarnings,
            'pending_earnings' => $pendingEarnings,
            'referral_percent' => $referralPercent,
            'referral_code'    => $referralCode,
        ]);
    }

    /**
     * @return array{referral_code: string|null}
     * @throws Exception
     */
    private function profile(int $userId): array
    {
        $row = $this->connection->createQueryBuilder()
            ->select('referral_code')
            ->from('user_profiles')
            ->where('user_id = :userId')
            ->setParameter('userId', $userId)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if ($row === false) {
            return ['referral_code' => null];
        }

        return ['referral_code' => $row['referral_code'] !== null ? (string)$row['referral_code'] : null];
    }
}
