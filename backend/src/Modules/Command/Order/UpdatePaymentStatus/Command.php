<?php

declare(strict_types=1);

namespace App\Modules\Command\Order\UpdatePaymentStatus;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Command
{
    public function __construct(
        #[Assert\NotBlank]
        public string $orderId,
        #[Assert\NotBlank]
        public string $paymentStatus,
    ) {}
}
