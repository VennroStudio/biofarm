<?php

declare(strict_types=1);

namespace App\Modules\Entity\AppleAlbumArtist;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;

final class AppleAlbumArtistRepository
{
    /** @var EntityRepository<AppleAlbumArtist> */
    private EntityRepository $repo;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repo = $em->getRepository(AppleAlbumArtist::class);
        $this->em = $em;
    }

    public function getCount(): int
    {
        return $this->repo->count([]);
    }

    /** @throws Exception */
    public function getById(int $id): AppleAlbumArtist
    {
        if (!$albumArtist = $this->findById($id)) {
            throw new Exception('AppleAlbumArtist Not Found');
        }

        return $albumArtist;
    }

    public function findById(int $id): ?AppleAlbumArtist
    {
        return $this->repo->findOneBy(['id' => $id]);
    }

    public function findByAlbumAndArtistIds(int $albumId, int $artistId): ?AppleAlbumArtist
    {
        return $this->repo->findOneBy(['albumId' => $albumId, 'artistId' => $artistId]);
    }

    public function add(AppleAlbumArtist $albumArtist): void
    {
        $this->em->persist($albumArtist);
    }

    public function remove(AppleAlbumArtist $albumArtist): void
    {
        $this->em->remove($albumArtist);
    }
}
