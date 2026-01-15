<?php

declare(strict_types=1);

namespace App\Modules\Entity\AlbumArtist;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;

final class AlbumArtistRepository
{
    /** @var EntityRepository<AlbumArtist> */
    private EntityRepository $repo;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repo = $em->getRepository(AlbumArtist::class);
        $this->em = $em;
    }

    public function getCount(): int
    {
        return $this->repo->count([]);
    }

    /** @throws Exception */
    public function getById(int $id): AlbumArtist
    {
        if (!$albumArtist = $this->findById($id)) {
            throw new Exception('AlbumArtist Not Found');
        }

        return $albumArtist;
    }

    public function findById(int $id): ?AlbumArtist
    {
        return $this->repo->findOneBy(['id' => $id]);
    }

    public function findByAlbumAndArtistIds(int $albumId, int $artistId): ?AlbumArtist
    {
        return $this->repo->findOneBy(['albumId' => $albumId, 'artistId' => $artistId]);
    }

    public function findFirstLoadedByAlbumId(int $albumId): ?AlbumArtist
    {
        return $this->repo->findOneBy(['albumId' => $albumId, 'isLoaded' => 1], ['id' => 'ASC']);
    }

    public function findFirstByAlbumId(int $albumId): ?AlbumArtist
    {
        return $this->repo->findOneBy(['albumId' => $albumId], ['isLoaded' => 'DESC', 'id' => 'ASC']);
    }

    public function add(AlbumArtist $albumArtist): void
    {
        $this->em->persist($albumArtist);
    }

    public function remove(AlbumArtist $albumArtist): void
    {
        $this->em->remove($albumArtist);
    }
}
