<?php

declare(strict_types=1);

namespace App\Modules\Payment\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'checkout_requests')]
class CheckoutRequest
{
    #[ORM\Id, ORM\Column(length: 64)] public string $id;
    #[ORM\Column(name: 'request_hash', length: 64)] public string $requestHash;
    #[ORM\Column(name: 'order_id', length: 50)] public string $orderId;
    #[ORM\Column(type: 'json')] public array $response;
    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')] public DateTimeImmutable $createdAt;
}
