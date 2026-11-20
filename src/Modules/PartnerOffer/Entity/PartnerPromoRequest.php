<?php

declare(strict_types=1);

namespace App\Modules\PartnerOffer\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity,ORM\Table(name: 'partner_promo_requests')]
#[ORM\Index(name: 'idx_partner_promo_user', columns: ['user_id'])]
#[ORM\UniqueConstraint(name: 'uniq_partner_promo', columns: ['promo_code_id'])]
class PartnerPromoRequest
{
    #[ORM\Id,ORM\Column(length: 64)] public string $id;
    #[ORM\Column(name: 'user_id', type: 'integer')] public int $userId;
    #[ORM\Column(length: 20)] public string $status;
    #[ORM\Column(type: 'json')] public array $request;
    #[ORM\Column(type: 'json')] public array $rules;
    #[ORM\Column(name: 'promo_code_id', type: 'integer', nullable: true)] public ?int $promoCodeId;
    #[ORM\Column(type: 'text', nullable: true)] public ?string $reason;
    #[ORM\Column(name: 'processed_by', type: 'integer', nullable: true)] public ?int $processedBy;
    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')] public DateTimeImmutable $createdAt;
    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')] public DateTimeImmutable $updatedAt;
}
