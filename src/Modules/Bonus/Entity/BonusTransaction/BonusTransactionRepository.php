<?php

declare(strict_types=1);

namespace App\Modules\Bonus\Entity\BonusTransaction;

interface BonusTransactionRepository
{
    public function add(BonusTransaction $transaction): void;
}
