<?php

declare(strict_types=1);

namespace App\Modules\Program\Entity\ProgramInvitation;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'program_invitations')]
#[ORM\UniqueConstraint(name: 'uniq_program_invitation_partner', columns: ['partner_id'])]
class ProgramInvitation
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 64)]
    private string $code;
    #[ORM\Column(type: 'integer')]
    private int $partnerId;
}
