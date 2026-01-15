<?php

declare(strict_types=1);

namespace App\Modules\Query\Artists\FindArtistNeedAppleUpdate;

use App\Modules\Constant;
use App\Modules\Entity\Artist\Artist;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Throwable;

final readonly class Fetcher
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function fetch(bool $isFull, array $excludedIds, ?int $mod): ?Artist
    {
        $queryBuilder = $this->em->createQueryBuilder();

        $queryBuilder
            ->select('artist')
            ->from(Artist::class, 'artist');

        if (null !== $mod) {
            $countWorkers = $isFull ? Constant::APPLE_COUNT_WORKERS : Constant::APPLE_CHECK_COUNT_WORKERS;
            $queryBuilder
                ->where('MOD(artist.id, :countWorkers) = :mod')
                ->setParameter('countWorkers', $countWorkers)
                ->setParameter('mod', $mod);
        }

        if ($isFull) {
            $queryBuilder->andWhere('artist.mergedAt IS NULL');
        } else {
            $queryBuilder->andWhere('artist.mergedAt IS NOT NULL');
        }

        if (\count($excludedIds) > 0) {
            $queryBuilder
                ->andWhere('artist.id NOT IN (:ids)')
                ->setParameter('ids', $excludedIds, ArrayParameterType::INTEGER);
        }

        $queryBuilder
            ->andWhere('
                artist.tidalCheckedAt IS NOT NULL AND
                artist.tidalCheckedAt > 0 AND
                (
                    artist.appleCheckedAt < artist.tidalCheckedAt OR
                    artist.appleCheckedAt IS NULL
                )
            ');

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
