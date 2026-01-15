<?php

declare(strict_types=1);

namespace App\Modules\Entity\AppleTrack;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;

final class AppleTrackRepository
{
    /** @var EntityRepository<AppleTrack> */
    private EntityRepository $repo;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repo = $em->getRepository(AppleTrack::class);
        $this->em = $em;
    }

    public function getCount(): int
    {
        return $this->repo->count([]);
    }

    /** @throws Exception */
    public function getById(int $id): AppleTrack
    {
        if (!$track = $this->findById($id)) {
            throw new Exception('AppleTrack Not Found');
        }

        return $track;
    }

    public function findById(int $id): ?AppleTrack
    {
        return $this->repo->findOneBy(['id' => $id]);
    }

    public function findByAppleId(string $id): ?AppleTrack
    {
        return $this->repo->findOneBy(['appleId' => $id]);
    }

    public function add(AppleTrack $track): void
    {
        $this->em->persist($track);
    }

    public function remove(AppleTrack $track): void
    {
        $this->em->remove($track);
    }
}
