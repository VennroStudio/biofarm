<?php

declare(strict_types=1);

namespace App\Modules\Command\Withdrawal\UpdateStatus;

use App\Modules\Entity\Withdrawal\Withdrawal;
use App\Modules\Entity\Withdrawal\WithdrawalRepository;

final readonly class Handler
{
    public function __construct(
        private WithdrawalRepository $withdrawalRepository,
    ) {}

    public function handle(Command $command): Withdrawal
    {
        $withdrawal = $this->withdrawalRepository->getById($command->withdrawalId);

        $withdrawal->updateStatus($command->status, $command->processedBy);

        return $withdrawal;
    }
}
