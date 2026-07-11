<?php

declare(strict_types=1);

namespace App\Modules\Bonus\Entity\BonusTransaction\Fields\Enums;

enum BonusTransactionType: string
{
    case ORDER_BONUS = 'order_bonus';
    case REFERRAL_BONUS = 'referral_bonus';
    case WITHDRAWAL = 'withdrawal';
    case MANUAL_ADJUSTMENT = 'manual_adjustment';
}
