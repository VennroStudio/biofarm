<?php

declare(strict_types=1);

namespace App\Modules\Entity\TrackAdditional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;

final class TrackAdditionalRepository
{
    /** @var EntityRepository<TrackAdditional> */
    private EntityRepository $repo;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repo = $em->getRepository(TrackAdditional::class);
        $this->em = $em;
    }

    /** @throws Exception */
    public function getById(int $id): TrackAdditional
    {
        if (!$trackAdditional = $this->findById($id)) {
            throw new Exception('TrackAdditional Not Found');
        }

        return $trackAdditional;
    }

    public function findById(int $id): ?TrackAdditional
    {
        return $this->repo->findOneBy(['id' => $id]);
    }

    public function findByTrackId(int $trackId): ?TrackAdditional
    {
        return $this->repo->findOneBy(['trackId' => $trackId]);
    }

    public function add(TrackAdditional $trackAdditional): void
    {
        $this->em->persist($trackAdditional);
    }

    public function remove(TrackAdditional $trackAdditional): void
    {
        $this->em->remove($trackAdditional);
    }
}
