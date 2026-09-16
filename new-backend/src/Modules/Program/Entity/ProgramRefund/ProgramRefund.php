<?php

declare(strict_types=1);

namespace App\Modules\Program\Entity\ProgramRefund;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'program_refunds')]
#[ORM\Index(name: 'idx_program_refund_order_status', columns: ['order_id', 'status'])]
class ProgramRefund
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 100)]
    private string $id;

    #[ORM\Column(type: 'string', length: 50)]
    private string $orderId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status;

    #[ORM\Column(type: 'json')]
    private array $payload;
}
