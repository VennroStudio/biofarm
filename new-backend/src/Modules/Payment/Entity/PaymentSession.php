<?php

declare(strict_types=1);

namespace App\Modules\Payment\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'payment_sessions')]
class PaymentSession
{
    #[ORM\Id, ORM\Column(name: 'order_id', length: 50)] public string $orderId;
    #[ORM\Column(name: 'token_hash', length: 64)] public string $tokenHash;
    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')] public DateTimeImmutable $createdAt;
}
