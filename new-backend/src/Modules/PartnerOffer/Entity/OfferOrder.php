<?php

declare(strict_types=1);

namespace App\Modules\PartnerOffer\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity,ORM\Table(name: 'partner_offer_orders')]
#[ORM\Index(name: 'idx_offer_order_offer', columns: ['offer_id'])]
class OfferOrder
{
    #[ORM\Id,ORM\Column(name: 'order_id', length: 50)] public string $orderId;
    #[ORM\Column(name: 'offer_id', length: 64)] public string $offerId;
}
