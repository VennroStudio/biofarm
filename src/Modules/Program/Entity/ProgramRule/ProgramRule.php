<?php

declare(strict_types=1);

namespace App\Modules\Program\Entity\ProgramRule;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'program_rules')]
class ProgramRule
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 64)]
    private string $id;

    #[ORM\Column(type: 'json')]
    private array $payload;

    #[ORM\Column(type: 'string', length: 30)]
    private string $createdAt;
}
