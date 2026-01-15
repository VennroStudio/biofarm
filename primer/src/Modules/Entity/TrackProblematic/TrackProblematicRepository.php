<?php

declare(strict_types=1);

namespace App\Modules\Entity\TrackProblematic;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use DomainException;

final class TrackProblematicRepository
{
    /** @var EntityRepository<TrackProblematic> */
    private EntityRepository $repo;

    public function __construct(private EntityManagerInterface $em)
    {
        $this->repo = $this->em->getRepository(TrackProblematic::class);
    }

    public function getById(int $id): TrackProblematic
    {
        $trackProblematic = $this->repo->find($id);

        if (!$trackProblematic instanceof TrackProblematic) {
            throw new DomainException('TrackProblematic not found');
        }

        return $trackProblematic;
    }

    public function findById(int $id): ?TrackProblematic
    {
        $result = $this->repo->find($id);

        return $result instanceof TrackProblematic ? $result : null;
    }

    public function findByLoTrackId(int $loTrackId): ?TrackProblematic
    {
        $result = $this->repo->findOneBy(['loTrackId' => $loTrackId]);

        return $result instanceof TrackProblematic ? $result : null;
    }

    public function add(TrackProblematic $trackProblematic): void
    {
        $this->em->persist($trackProblematic);
    }

    public function remove(TrackProblematic $trackProblematic): void
    {
        $this->em->remove($trackProblematic);
    }
}
