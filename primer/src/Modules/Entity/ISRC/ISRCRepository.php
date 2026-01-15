<?php

declare(strict_types=1);

namespace App\Modules\Entity\ISRC;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;

final class ISRCRepository
{
    /** @var EntityRepository<ISRC> */
    private EntityRepository $repo;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repo = $em->getRepository(ISRC::class);
        $this->em = $em;
    }

    /** @throws Exception */
    public function getById(int $id): ISRC
    {
        if (!$isrc = $this->findById($id)) {
            throw new Exception('ISRC Not Found');
        }

        return $isrc;
    }

    public function findById(int $id): ?ISRC
    {
        return $this->repo->findOneBy(['id' => $id]);
    }

    public function findByISRCId(string $isrcId): ?ISRC
    {
        return $this->repo->findOneBy(['isrcId' => $isrcId]);
    }

    public function add(ISRC $isrc): void
    {
        $this->em->persist($isrc);
    }

    public function remove(ISRC $isrc): void
    {
        $this->em->remove($isrc);
    }
}
