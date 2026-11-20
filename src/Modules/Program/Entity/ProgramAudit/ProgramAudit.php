<?php

declare(strict_types=1);

namespace App\Modules\Program\Entity\ProgramAudit;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'program_audit')]
#[ORM\Index(name: 'idx_program_audit_created', columns: ['created_at'])]
class ProgramAudit
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 64)]
    private string $id;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $actorId;

    #[ORM\Column(type: 'string', length: 40)]
    private string $kind;

    #[ORM\Column(type: 'json')]
    private array $payload;

    #[ORM\Column(type: 'string', length: 30)]
    private string $createdAt;
}
