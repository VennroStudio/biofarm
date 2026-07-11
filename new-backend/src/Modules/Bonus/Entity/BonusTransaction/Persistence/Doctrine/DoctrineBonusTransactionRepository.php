<?php

declare(strict_types=1);

namespace App\Modules\Bonus\Entity\BonusTransaction\Persistence\Doctrine;

use App\Modules\Bonus\Entity\BonusTransaction\BonusTransaction;
use App\Modules\Bonus\Entity\BonusTransaction\BonusTransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Override;

final readonly class DoctrineBonusTransactionRepository implements BonusTransactionRepository
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    #[Override]
    public function add(BonusTransaction $transaction): void
    {
        $this->em->persist($transaction);
    }
}
