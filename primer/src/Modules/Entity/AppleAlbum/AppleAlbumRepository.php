<?php

declare(strict_types=1);

namespace App\Modules\Entity\AppleAlbum;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;

final class AppleAlbumRepository
{
    /** @var EntityRepository<AppleAlbum> */
    private EntityRepository $repo;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repo = $em->getRepository(AppleAlbum::class);
        $this->em = $em;
    }

    public function getCount(): int
    {
        return $this->repo->count([]);
    }

    /** @throws Exception */
    public function getById(int $id): AppleAlbum
    {
        if (!$appleAlbum = $this->findById($id)) {
            throw new Exception('AppleAlbum Not Found');
        }

        return $appleAlbum;
    }

    public function findById(int $id): ?AppleAlbum
    {
        return $this->repo->findOneBy(['id' => $id]);
    }

    public function findByAppleId(string $id): ?AppleAlbum
    {
        return $this->repo->findOneBy(['appleId' => $id]);
    }

    public function add(AppleAlbum $appleAlbum): void
    {
        $this->em->persist($appleAlbum);
    }

    public function remove(AppleAlbum $appleAlbum): void
    {
        $this->em->remove($appleAlbum);
    }
}
