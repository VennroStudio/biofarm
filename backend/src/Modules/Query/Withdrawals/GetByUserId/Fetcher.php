<?php

declare(strict_types=1);

namespace App\Modules\Query\Withdrawals\GetByUserId;

use App\Modules\Entity\Withdrawal\Withdrawal;
use App\Modules\Entity\Withdrawal\WithdrawalRepository;

final readonly class Fetcher
{
    public function __construct(
        private WithdrawalRepository $withdrawalRepository,
    ) {}

    /** @return Withdrawal[] */
    public function fetch(Query $query): array
    {
        return $this->withdrawalRepository->findByUserId($query->userId);
    }
}
