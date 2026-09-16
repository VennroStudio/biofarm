<?php

declare(strict_types=1);

namespace App\Modules\Order\Service;

use App\Components\Flusher\FlusherInterface;
use App\Modules\Order\Entity\Order\Order;
use App\Modules\Payment\Service\PaymentService;
use App\Modules\Program\Service\ProgramService;

final readonly class OrderBonusApplier
{
    public function __construct(private ProgramService $program, private FlusherInterface $flusher, private PaymentService $payments) {}

    public function apply(Order $order): void
    {
        $this->program->atomic(function () use ($order): void {
            $this->flusher->flush();
            if ($order->paymentStatus === 'completed') {
                $this->program->settleOrder($order->id);
            }
            if ($order->status === 'cancelled') {
                $this->program->cancelOrder($order->id);
            }
            if ($order->status === 'delivered') {
                $this->program->deliverOrder($order->id);
                $this->payments->queueSettlementReceipt($order->id);
            }
        });
    }
}
