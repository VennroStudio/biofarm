<?php

declare(strict_types=1);

namespace App\Modules\Entity\ArtistProblematic;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use DomainException;

final class ArtistProblematicRepository
{
    /** @var EntityRepository<ArtistProblematic> */
    private EntityRepository $repo;

    public function __construct(private EntityManagerInterface $em)
    {
        $this->repo = $this->em->getRepository(ArtistProblematic::class);
    }

    public function getById(int $id): ArtistProblematic
    {
        $artistProblematic = $this->repo->find($id);

        if (!$artistProblematic instanceof ArtistProblematic) {
            throw new DomainException('ArtistProblematic not found');
        }

        return $artistProblematic;
    }

    public function findById(int $id): ?ArtistProblematic
    {
        $result = $this->repo->find($id);

        return $result instanceof ArtistProblematic ? $result : null;
    }

    public function findByArtistId(int $artistId): ?ArtistProblematic
    {
        $result = $this->repo->findOneBy(['artistId' => $artistId]);

        return $result instanceof ArtistProblematic ? $result : null;
    }

    public function add(ArtistProblematic $artistProblematic): void
    {
        $this->em->persist($artistProblematic);
    }

    public function remove(ArtistProblematic $artistProblematic): void
    {
        $this->em->remove($artistProblematic);
    }
}
