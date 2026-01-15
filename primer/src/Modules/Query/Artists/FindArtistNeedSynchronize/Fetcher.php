<?php

declare(strict_types=1);

namespace App\Modules\Query\Artists\FindArtistNeedSynchronize;

use App\Modules\Constant;
use App\Modules\Entity\Artist\Artist;
use Doctrine\ORM\EntityManagerInterface;
use Throwable;

final readonly class Fetcher
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function fetch(?int $mod = null): ?Artist
    {
        $queryBuilder = $this->em->createQueryBuilder();

        $queryBuilder
            ->select('artist')
            ->from(Artist::class, 'artist');

        if (null !== $mod) {
            $queryBuilder
                ->andWhere('MOD(artist.id, :countWorkers) = :mod')
                ->setParameter('countWorkers', Constant::SYNCHRONIZE_COUNT_WORKERS)
                ->setParameter('mod', $mod);
        }

        $queryBuilder
            ->andWhere('artist.synchronizedAt IS NULL');

        $queryBuilder
            ->orderBy('artist.priority', 'DESC')
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
