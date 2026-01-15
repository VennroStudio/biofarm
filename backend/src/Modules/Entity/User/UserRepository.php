<?php

declare(strict_types=1);

namespace App\Modules\Entity\User;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;

final class UserRepository
{
    /** @var EntityRepository<User> */
    private EntityRepository $repo;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repo = $em->getRepository(User::class);
        $this->em = $em;
    }

    public function getCount(): int
    {
        return $this->repo->count([]);
    }

    /** @throws Exception */
    public function getById(int $id): User
    {
        if (!$user = $this->findById($id)) {
            throw new Exception('User Not Found');
        }

        return $user;
    }

    public function findById(int $id): ?User
    {
        return $this->repo->findOneBy(['id' => $id]);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->repo->findOneBy(['email' => $email]);
    }

    /** @return User[] */
    public function findByReferredBy(string $referredBy): array
    {
        return $this->repo->findBy(['referredBy' => $referredBy, 'isActive' => true]);
    }

    /** @return User[] */
    public function findAllActive(): array
    {
        return $this->repo->findBy(['isActive' => true], ['createdAt' => 'DESC']);
    }

    public function add(User $user): void
    {
        $this->em->persist($user);
    }

    public function remove(User $user): void
    {
        $this->em->remove($user);
    }
}
