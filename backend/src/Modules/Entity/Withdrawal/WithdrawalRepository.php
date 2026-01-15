<?php

declare(strict_types=1);

namespace App\Modules\Entity\Withdrawal;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;

final class WithdrawalRepository
{
    /** @var EntityRepository<Withdrawal> */
    private EntityRepository $repo;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repo = $em->getRepository(Withdrawal::class);
        $this->em = $em;
    }

    public function getCount(): int
    {
        return $this->repo->count([]);
    }

    /** @throws Exception */
    public function getById(string $id): Withdrawal
    {
        if (!$withdrawal = $this->findById($id)) {
            throw new Exception('Withdrawal Not Found');
        }

        return $withdrawal;
    }

    public function findById(string $id): ?Withdrawal
    {
        return $this->repo->findOneBy(['id' => $id]);
    }

    /** @return Withdrawal[] */
    public function findByUserId(int $userId): array
    {
        return $this->repo->findBy(['userId' => $userId], ['createdAt' => 'DESC']);
    }

    /** @return Withdrawal[] */
    public function findAll(): array
    {
        return $this->repo->findBy([], ['createdAt' => 'DESC']);
    }

    public function add(Withdrawal $withdrawal): void
    {
        $this->em->persist($withdrawal);
    }

    public function remove(Withdrawal $withdrawal): void
    {
        $this->em->remove($withdrawal);
    }
}
