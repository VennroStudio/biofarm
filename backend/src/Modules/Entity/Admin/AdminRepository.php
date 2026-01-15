<?php

declare(strict_types=1);

namespace App\Modules\Entity\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;

final class AdminRepository
{
    /** @var EntityRepository<Admin> */
    private EntityRepository $repo;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repo = $em->getRepository(Admin::class);
        $this->em = $em;
    }

    /** @throws Exception */
    public function getById(int $id): Admin
    {
        if (!$admin = $this->findById($id)) {
            throw new Exception('Admin Not Found');
        }

        return $admin;
    }

    public function findById(int $id): ?Admin
    {
        return $this->repo->findOneBy(['id' => $id, 'isActive' => true]);
    }

    public function findByEmail(string $email): ?Admin
    {
        return $this->repo->findOneBy(['email' => $email, 'isActive' => true]);
    }

    /** @return Admin[] */
    public function findAll(): array
    {
        return $this->repo->findBy(['isActive' => true], ['createdAt' => 'DESC']);
    }

    public function add(Admin $admin): void
    {
        $this->em->persist($admin);
    }

    public function remove(Admin $admin): void
    {
        $this->em->remove($admin);
    }
}
