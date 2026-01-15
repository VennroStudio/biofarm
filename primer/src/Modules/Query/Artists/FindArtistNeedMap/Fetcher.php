<?php

declare(strict_types=1);

namespace App\Modules\Query\Artists\FindArtistNeedMap;

use App\Modules\Constant;
use App\Modules\Entity\Artist\Artist;
use Doctrine\ORM\EntityManagerInterface;
use Throwable;

final readonly class Fetcher
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function fetch(bool $isFull, ?int $mod = null): ?Artist
    {
        $queryBuilder = $this->em->createQueryBuilder();

        $queryBuilder
            ->select('artist')
            ->from(Artist::class, 'artist');

        if (null !== $mod) {
            $countWorkers = $isFull ? Constant::MAPPER_COUNT_WORKERS : Constant::MAPPER_CHECK_COUNT_WORKERS;
            $queryBuilder
                ->where('MOD(artist.id, :countWorkers) = :mod')
                ->setParameter('countWorkers', $countWorkers)
                ->setParameter('mod', $mod);
        }

        $queryBuilder
            ->andWhere('artist.appleCheckedAt IS NOT NULL');

        if ($isFull) {
            $queryBuilder
                ->andWhere('artist.mergedAt IS NULL');
        } else {
            $queryBuilder
                ->andWhere('artist.mergedAt < artist.appleCheckedAt AND artist.mergedAt IS NOT NULL');
        }

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
