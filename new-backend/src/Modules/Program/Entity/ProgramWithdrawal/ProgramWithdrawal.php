<?php

declare(strict_types=1);

namespace App\Modules\Program\Entity\ProgramWithdrawal;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'program_withdrawals')]
#[ORM\Index(name: 'idx_program_withdrawal_user_status', columns: ['user_id', 'status'])]
class ProgramWithdrawal
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 64)]
    private string $id;

    #[ORM\Column(type: 'integer')]
    private int $userId;

    #[ORM\Column(type: 'bigint')]
    private int $amountMinor;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $reference;

    #[ORM\Column(type: 'json')]
    private array $details;

    #[ORM\Column(type: 'string', length: 30)]
    private string $createdAt;
}
