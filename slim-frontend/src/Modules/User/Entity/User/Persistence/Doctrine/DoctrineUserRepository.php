<?php

declare(strict_types=1);

namespace App\Modules\User\Entity\User\Persistence\Doctrine;

use App\Components\Exception\DomainExceptionModule;
use App\Modules\User\Entity\User\User;
use App\Modules\User\Entity\User\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Override;

final readonly class DoctrineUserRepository implements UserRepository
{
    /** @var EntityRepository<User> */
    private EntityRepository $repo;

    public function __construct(
        private EntityManagerInterface $em,
    ) {
        $this->repo = $em->getRepository(User::class);
    }

    #[Override]
    public function add(User $user): void
    {
        $this->em->persist($user);
    }

    #[Override]
    public function remove(User $user): void
    {
        $this->em->remove($user);
    }

    #[Override]
    public function getById(int $id): User
    {
        if (!$user = $this->findById($id)) {
            throw new DomainExceptionModule(
                module: 'user',
                message: 'error.user_not_found',
                code: 1
            );
        }

        return $user;
    }

    #[Override]
    public function findById(int $id): ?User
    {
        return $this->repo->findOneBy(['id' => $id]);
    }
}
