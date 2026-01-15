<?php

declare(strict_types=1);

namespace App\Modules\Query\Artists\FindArtistNeedSimilarUpdate;

use App\Modules\Constant;
use App\Modules\Entity\Artist\Artist;
use Doctrine\ORM\EntityManagerInterface;
use Throwable;

final readonly class Fetcher
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function fetch(?int $mod): ?Artist
    {
        $queryBuilder = $this->em->createQueryBuilder();

        $queryBuilder
            ->select('artist')
            ->from(Artist::class, 'artist');

        if (null !== $mod) {
            $queryBuilder
                ->andWhere('MOD(artist.id, :countWorkers) = :mod')
                ->setParameter('countWorkers', Constant::RATE_ARTIST_COUNT_WORKERS)
                ->setParameter('mod', $mod);
        }

        $queryBuilder
            ->orderBy('artist.rateCheckedAt', 'ASC')
            ->addOrderBy('artist.id', 'ASC')
            ->setMaxResults(1);

        try {
            /** @var Artist|null */
            return $queryBuilder->getQuery()->getOneOrNullResult();
        } catch (Throwable) {
        }

        return null;
    }
}
