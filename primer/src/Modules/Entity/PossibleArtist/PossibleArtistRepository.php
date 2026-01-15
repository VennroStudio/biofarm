<?php

declare(strict_types=1);

namespace App\Modules\Entity\PossibleArtist;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;

final class PossibleArtistRepository
{
    /** @var EntityRepository<PossibleArtist> */
    private EntityRepository $repo;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repo = $em->getRepository(PossibleArtist::class);
        $this->em = $em;
    }

    public function getCount(): int
    {
        return $this->repo->count(['artistId' => null]);
    }

    /** @throws Exception */
    public function getById(int $id): PossibleArtist
    {
        if (!$possibleArtist = $this->findById($id)) {
            throw new Exception('PossibleArtist Not Found');
        }

        return $possibleArtist;
    }

    public function findById(int $id): ?PossibleArtist
    {
        return $this->repo->findOneBy(['id' => $id, 'deletedAt' => null]);
    }

    public function findBySpotifyId(string $spotifyId): ?PossibleArtist
    {
        return $this->repo->findOneBy(['spotifyId' => $spotifyId]);
    }

    public function findByAppleId(string $appleId): ?PossibleArtist
    {
        return $this->repo->findOneBy(['appleId' => $appleId]);
    }

    public function findByTidalId(string $tidalId): ?PossibleArtist
    {
        return $this->repo->findOneBy(['tidalId' => $tidalId]);
    }

    public function add(PossibleArtist $possibleArtist): void
    {
        $this->em->persist($possibleArtist);
    }

    public function remove(PossibleArtist $possibleArtist): void
    {
        $this->em->remove($possibleArtist);
    }
}
