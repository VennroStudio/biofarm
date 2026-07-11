<?php

declare(strict_types=1);

namespace App\Modules\Withdrawal\Entity\WithdrawalRequest;

interface WithdrawalRequestRepository
{
    public function add(WithdrawalRequest $request): void;

    public function getById(string $id): WithdrawalRequest;

    public function findById(string $id): ?WithdrawalRequest;
}
