<?php

declare(strict_types=1);

namespace App\Modules\Program\Entity\ProgramMember;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'program_members')]
#[ORM\Index(name: 'idx_program_members_partner', columns: ['partner_id'])]
class ProgramMember
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private int $userId;
    #[ORM\Column(type: 'integer')]
    private int $partnerId;
    #[ORM\Column(type: 'string', length: 30)]
    private string $joinedAt;
}
