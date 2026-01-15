<?php

declare(strict_types=1);

namespace App\Modules\Query\Album\FindAlbumNeedSimilarUpdate;

use App\Modules\Entity\Album\Album;
use Doctrine\ORM\EntityManagerInterface;
use Throwable;

final readonly class Fetcher
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function fetch(): ?Album
    {
        $queryBuilder = $this->em->createQueryBuilder();

        $queryBuilder
            ->select('albums')
            ->from(Album::class, 'albums')
            ->andWhere('albums.similarCheckedAt IS NULL')
            ->andWhere('albums.loAlbumId IS NOT NULL')
            ->andWhere('albums.allTracksMapped = 1');

        $queryBuilder
            ->orderBy('albums.id', 'ASC')
            ->setMaxResults(1);

        try {
            /** @var Album|null */
            return $queryBuilder->getQuery()->getOneOrNullResult();
        } catch (Throwable) {
        }

        return null;
    }
}
