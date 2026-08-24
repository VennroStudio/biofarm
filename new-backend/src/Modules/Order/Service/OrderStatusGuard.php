<?php

declare(strict_types=1);

namespace App\Modules\Order\Service;

use App\Components\Exception\DomainExceptionModule;

final readonly class OrderStatusGuard
{
    /** @var list<string> */
    private const array ORDER_STATUSES = [
        'pending',
        'processing',
        'shipped',
        'delivered',
        'cancelled',
    ];

    /** @var list<string> */
    private const array PAYMENT_STATUSES = [
        'pending',
        'completed',
        'failed',
        'refunded',
    ];

    public function orderStatus(string $status): string
    {
        $status = trim($status);
        if (!\in_array($status, self::ORDER_STATUSES, true)) {
            throw new DomainExceptionModule(
                module: 'order',
                message: 'error.invalid_order_status',
                code: 31,
                status: 422,
            );
        }

        return $status;
    }

    public function paymentStatus(string $status): string
    {
        $status = trim($status);
        if (!\in_array($status, self::PAYMENT_STATUSES, true)) {
            throw new DomainExceptionModule(
                module: 'order',
                message: 'error.invalid_payment_status',
                code: 32,
                status: 422,
            );
        }

        return $status;
    }
}
