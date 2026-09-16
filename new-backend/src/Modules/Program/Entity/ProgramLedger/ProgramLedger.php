<?php

declare(strict_types=1);

namespace App\Modules\Program\Entity\ProgramLedger;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'program_ledger')]
#[ORM\Index(name: 'idx_program_ledger_user_wallet_state', columns: ['user_id', 'wallet', 'state'])]
#[ORM\Index(name: 'idx_program_ledger_order', columns: ['order_id'])]
#[ORM\Index(name: 'idx_program_ledger_release', columns: ['state', 'available_at'])]
class ProgramLedger
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 100)]
    private string $id;

    #[ORM\Column(type: 'integer')]
    private int $userId;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $orderId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $wallet;

    #[ORM\Column(type: 'bigint')]
    private int $amountMinor;

    #[ORM\Column(type: 'string', length: 20)]
    private string $state;

    #[ORM\Column(type: 'string', length: 30)]
    private string $kind;

    #[ORM\Column(type: 'string', length: 30)]
    private string $createdAt;

    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    private ?string $availableAt;

    #[ORM\Column(type: 'json')]
    private array $details;
}
