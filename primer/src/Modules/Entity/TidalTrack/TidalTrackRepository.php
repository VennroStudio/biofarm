<?php

declare(strict_types=1);

namespace App\Modules\Entity\TidalTrack;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;

final class TidalTrackRepository
{
    /** @var EntityRepository<TidalTrack> */
    private EntityRepository $repo;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repo = $em->getRepository(TidalTrack::class);
        $this->em = $em;
    }

    public function getCount(): int
    {
        return $this->repo->count([]);
    }

    /** @throws Exception */
    public function getById(int $id): TidalTrack
    {
        if (!$track = $this->findById($id)) {
            throw new Exception('TidalTrack Not Found');
        }

        return $track;
    }

    public function findById(int $id): ?TidalTrack
    {
        return $this->repo->findOneBy(['id' => $id]);
    }

    public function findByTidalId(string $id): ?TidalTrack
    {
        return $this->repo->findOneBy(['tidalId' => $id]);
    }

    public function add(TidalTrack $track): void
    {
        $this->em->persist($track);
    }

    public function remove(TidalTrack $track): void
    {
        $this->em->remove($track);
    }
}
