<?php

declare(strict_types=1);

namespace App\Modules\Entity\TidalAlbumArtist;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;

final class TidalAlbumArtistRepository
{
    /** @var EntityRepository<TidalAlbumArtist> */
    private EntityRepository $repo;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repo = $em->getRepository(TidalAlbumArtist::class);
        $this->em = $em;
    }

    public function getCount(): int
    {
        return $this->repo->count([]);
    }

    /** @throws Exception */
    public function getById(int $id): TidalAlbumArtist
    {
        if (!$albumArtist = $this->findById($id)) {
            throw new Exception('TidalAlbumArtist Not Found');
        }

        return $albumArtist;
    }

    public function findById(int $id): ?TidalAlbumArtist
    {
        return $this->repo->findOneBy(['id' => $id]);
    }

    public function findByAlbumAndArtistIds(int $albumId, int $artistId): ?TidalAlbumArtist
    {
        return $this->repo->findOneBy(['albumId' => $albumId, 'artistId' => $artistId]);
    }

    public function add(TidalAlbumArtist $albumArtist): void
    {
        $this->em->persist($albumArtist);
    }

    public function remove(TidalAlbumArtist $albumArtist): void
    {
        $this->em->remove($albumArtist);
    }
}
