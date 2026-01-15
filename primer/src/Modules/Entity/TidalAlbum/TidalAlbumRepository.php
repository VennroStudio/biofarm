<?php

declare(strict_types=1);

namespace App\Modules\Entity\TidalAlbum;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;

final class TidalAlbumRepository
{
    /** @var EntityRepository<TidalAlbum> */
    private EntityRepository $repo;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repo = $em->getRepository(TidalAlbum::class);
        $this->em = $em;
    }

    public function getCount(): int
    {
        return $this->repo->count([]);
    }

    /** @throws Exception */
    public function getById(int $id): TidalAlbum
    {
        if (!$tidalAlbum = $this->findById($id)) {
            throw new Exception('TidalAlbum Not Found');
        }

        return $tidalAlbum;
    }

    public function findById(int $id): ?TidalAlbum
    {
        return $this->repo->findOneBy(['id' => $id]);
    }

    public function findByTidalId(string $id): ?TidalAlbum
    {
        return $this->repo->findOneBy(['tidalId' => $id]);
    }

    public function add(TidalAlbum $tidalAlbum): void
    {
        $this->em->persist($tidalAlbum);
    }

    public function remove(TidalAlbum $tidalAlbum): void
    {
        $this->em->remove($tidalAlbum);
    }
}
