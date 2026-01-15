<?php

declare(strict_types=1);

namespace App\Modules\Entity\Artist;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;

final class ArtistRepository
{
    /** @var EntityRepository<Artist> */
    private EntityRepository $repo;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repo = $em->getRepository(Artist::class);
        $this->em = $em;
    }

    public function getCount(): int
    {
        return $this->repo->count([]);
    }

    /** @throws Exception */
    public function getById(int $id): Artist
    {
        if (!$artist = $this->findById($id)) {
            throw new Exception('Artist Not Found');
        }

        return $artist;
    }

    public function findById(int $id): ?Artist
    {
        return $this->repo->findOneBy(['id' => $id]);
    }

    public function findByUnionId(int $unionId): ?Artist
    {
        return $this->repo->findOneBy(['unionId' => $unionId]);
    }

    public function findByDescription(string $description): ?Artist
    {
        return $this->repo->findOneBy(['description' => $description]);
    }

    public function add(Artist $artist): void
    {
        $this->em->persist($artist);
    }

    public function remove(Artist $artist): void
    {
        $this->em->remove($artist);
    }
}
