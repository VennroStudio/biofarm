<?php

declare(strict_types=1);

namespace App\Modules\PartnerOffer\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity, ORM\Table(name: 'partner_offers')]
#[ORM\Index(name: 'idx_offer_owner', columns: ['user_id'])]
class PartnerOffer
{
    #[ORM\Id,ORM\Column(length: 64)] public string $id;
    #[ORM\Column(name: 'user_id', type: 'integer')] public int $userId;
    #[ORM\Column(length: 150)] public string $title;
    #[ORM\Column(type: 'json')] public array $items;
    #[ORM\Column(name: 'promo_code', length: 100, nullable: true)] public ?string $promoCode;
    #[ORM\Column(name: 'expires_at', type: 'datetime_immutable', nullable: true)] public ?DateTimeImmutable $expiresAt;
    #[ORM\Column(name: 'is_active', type: 'boolean')] public bool $isActive;
    #[ORM\Column(type: 'integer')] public int $visits;
    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')] public DateTimeImmutable $createdAt;
}
