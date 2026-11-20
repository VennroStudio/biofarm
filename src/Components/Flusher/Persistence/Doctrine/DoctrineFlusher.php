<?php

declare(strict_types=1);

namespace App\Components\Flusher\Persistence\Doctrine;

use App\Components\Flusher\FlusherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Override;

final readonly class DoctrineFlusher implements FlusherInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    #[Override]
    public function flush(): void
    {
        $this->em->flush();
    }
}
