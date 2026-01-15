<?php

declare(strict_types=1);

namespace App\Modules\Command\Withdrawal\Create;

use App\Modules\Entity\Withdrawal\Withdrawal;
use App\Modules\Entity\Withdrawal\WithdrawalRepository;

final readonly class Handler
{
    public function __construct(
        private WithdrawalRepository $withdrawalRepository,
    ) {}

    public function handle(Command $command): Withdrawal
    {
        $withdrawal = Withdrawal::create(
            id: $command->withdrawalId,
            userId: $command->userId,
            amount: $command->amount,
            status: $command->status,
        );

        $this->withdrawalRepository->add($withdrawal);

        return $withdrawal;
    }
}
