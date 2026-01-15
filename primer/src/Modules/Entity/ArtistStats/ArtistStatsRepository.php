<?php

declare(strict_types=1);

namespace App\Modules\Entity\ArtistStats;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;

final class ArtistStatsRepository
{
    /** @var EntityRepository<ArtistStats> */
    private EntityRepository $repo;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repo = $em->getRepository(ArtistStats::class);
        $this->em = $em;
    }

    /** @throws Exception */
    public function getById(int $id): ArtistStats
    {
        if (!$artistStats = $this->findById($id)) {
            throw new Exception('ArtistStats Not Found');
        }

        return $artistStats;
    }

    public function findById(int $id): ?ArtistStats
    {
        return $this->repo->findOneBy(['id' => $id]);
    }

    public function findByArtistId(int $artistId): ?ArtistStats
    {
        return $this->repo->findOneBy(['artistId' => $artistId]);
    }

    public function add(ArtistStats $artistStats): void
    {
        $this->em->persist($artistStats);
    }

    public function remove(ArtistStats $artistStats): void
    {
        $this->em->remove($artistStats);
    }
}
