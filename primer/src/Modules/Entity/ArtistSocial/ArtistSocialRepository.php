<?php

declare(strict_types=1);

namespace App\Modules\Entity\ArtistSocial;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;

final class ArtistSocialRepository
{
    /** @var EntityRepository<ArtistSocial> */
    private EntityRepository $repo;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repo = $em->getRepository(ArtistSocial::class);
        $this->em = $em;
    }

    public function getCount(): int
    {
        return $this->repo->count([]);
    }

    /** @throws Exception */
    public function getById(int $id): ArtistSocial
    {
        if (!$artistSocial = $this->findById($id)) {
            throw new Exception('ArtistSocial Not Found');
        }

        return $artistSocial;
    }

    public function findById(int $id): ?ArtistSocial
    {
        return $this->repo->findOneBy(['id' => $id, 'deletedAt' => null]);
    }

    public function findByUrl(int $artistId, string $url): ?ArtistSocial
    {
        return $this->repo->findOneBy(['artistId' => $artistId, 'url' => $url, 'deletedAt' => null]);
    }

    public function findByTypeAndPartUrl(int $type, string $url): ?ArtistSocial
    {
        $query = $this->em->createQueryBuilder()
            ->select('a')
            ->from(ArtistSocial::class, 'a')
            ->where('a.url LIKE :url')
            ->andWhere('a.type = :type')
            ->andWhere('a.deletedAt IS NULL')
            ->setParameter('url', '%/' . $url . '%')
            ->setParameter('type', $type)
            ->setMaxResults(1)
            ->getQuery();

        /** @var ?ArtistSocial */
        return $query->getOneOrNullResult();
    }

    public function add(ArtistSocial $artistSocial): void
    {
        $this->em->persist($artistSocial);
    }

    public function remove(ArtistSocial $artistSocial): void
    {
        $this->em->remove($artistSocial);
    }
}
