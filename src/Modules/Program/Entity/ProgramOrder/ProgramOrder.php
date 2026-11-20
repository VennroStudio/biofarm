<?php

declare(strict_types=1);

namespace App\Modules\Program\Entity\ProgramOrder;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'program_orders')]
#[ORM\Index(name: 'idx_program_order_buyer_status', columns: ['buyer_id', 'status'])]
class ProgramOrder
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 50)]
    private string $id;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $buyerId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status;

    #[ORM\Column(type: 'json')]
    private array $snapshot;

    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    private ?string $deliveredAt;
}
