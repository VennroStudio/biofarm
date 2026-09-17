<?php

declare(strict_types=1);

namespace App\Http\Action\v1\User;

use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataResponse;
use App\Modules\Program\Service\ProgramService;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class GetReferralInfoAction implements RequestHandlerInterface
{
    public function __construct(private ProgramService $program, private Connection $connection) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = RequestIdentity::get($request)->id;
        $dashboard = $this->program->dashboard($id);
        $wallet = $dashboard['identity']['canEarnCommission'] ? 'commission' : 'shopping';
        return new JsonDataResponse([
            'referred_users'   => (int)$this->connection->fetchOne('SELECT COUNT(*) FROM user_profiles WHERE referred_by_user_id=?', [$id]),
            'total_earnings'   => (int)$this->connection->fetchOne("SELECT COALESCE(SUM(amount_minor),0) FROM program_ledger WHERE user_id=? AND wallet=? AND kind IN ('direct','team','referral_bonus','refund') AND state IN ('available','pending')", [$id, $wallet]) / 100,
            'pending_earnings' => $dashboard['balances'][$wallet]['pendingMinor'] / 100,
            'referral_percent' => $dashboard['rates'][$wallet === 'commission' ? ($dashboard['identity']['isPartner'] ? 'partnerDirectBps' : 'memberDirectBps') : 'referralBonusBps'] / 100,
            'referral_code'    => $dashboard['identity']['referralCode'],
        ]);
    }
}
