<?php

declare(strict_types=1);

namespace App\Modules\Payment\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'payment_operations')]
#[ORM\UniqueConstraint(name: 'uniq_payment_provider', columns: ['provider_id'])]
#[ORM\Index(name: 'idx_payment_order', columns: ['order_id', 'kind'])]
class PaymentOperation
{
    #[ORM\Id, ORM\Column(length: 64)] public string $id;
    #[ORM\Column(name: 'order_id', length: 50)] public string $orderId;
    #[ORM\Column(length: 12)] public string $kind;
    #[ORM\Column(name: 'provider_id', length: 64, nullable: true)] public ?string $providerId;
    #[ORM\Column(name: 'amount_minor', type: 'integer')] public int $amountMinor;
    #[ORM\Column(length: 20)] public string $status;
    #[ORM\Column(name: 'request_payload', type: 'json')] public array $requestPayload;
    #[ORM\Column(name: 'confirmation_url', type: 'text', nullable: true)] public ?string $confirmationUrl;
    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')] public DateTimeImmutable $createdAt;
    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')] public DateTimeImmutable $updatedAt;
}
