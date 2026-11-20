<?php

declare(strict_types=1);

namespace App\Modules\Program\Entity\ProgramLock;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'program_locks')]
class ProgramLock
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 40)]
    private string $id;

    #[ORM\Column(type: 'json')]
    private array $payload;
}
