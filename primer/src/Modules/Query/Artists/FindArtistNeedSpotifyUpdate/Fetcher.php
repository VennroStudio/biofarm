<?php

declare(strict_types=1);

namespace App\Modules\Query\Artists\FindArtistNeedSpotifyUpdate;

use App\Modules\Constant;
use App\Modules\Entity\Artist\Artist;
use App\Modules\Entity\ArtistStats\ArtistStats;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Throwable;

final readonly class Fetcher
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function fetch(bool $isFull, ?int $mod): ?Artist
    {
        $queryBuilder = $this->em->createQueryBuilder();

        $queryBuilder
            ->select('artist')
            ->from(Artist::class, 'artist')
            ->innerJoin(ArtistStats::class, 'stats', Join::WITH, 'stats.artistId = artist.id')
            ->andWhere('stats.spotifyCountSocials > 0');

        if (null !== $mod) {
            $countWorkers = $isFull ? Constant::SPOTIFY_COUNT_WORKERS : Constant::SPOTIFY_CHECK_COUNT_WORKERS;

            $queryBuilder
                ->andWhere('MOD(artist.id, :countWorkers) = :mod')
                ->setParameter('countWorkers', $countWorkers)
                ->setParameter('mod', $mod);
        }

        if ($isFull) {
            $queryBuilder
                ->andWhere('
                    artist.spotifyCheckedAt IS NULL OR
                    artist.spotifyCheckedAt <= 0 OR
                    (artist.mergedAt IS NULL AND artist.spotifyCheckedAt < :time)
                ');
        } else {
            $queryBuilder
                ->andWhere('artist.mergedAt IS NOT NULL')
                ->andWhere('artist.spotifyCheckedAt IS NOT NULL AND artist.spotifyCheckedAt < :time');
        }

        $queryBuilder
            ->setParameter('time', Constant::timeFrom());

        $queryBuilder
            ->orderBy('artist.priority', 'DESC')
            ->addOrderBy('artist.spotifyCheckedAt', 'ASC')
            ->addOrderBy('artist.id', 'ASC')
            ->setMaxResults(1);

        try {
            /** @var Artist|null */
            return $queryBuilder->getQuery()->getOneOrNullResult();
        } catch (Throwable $e) {
            echo $e->getMessage();
        }

        return null;
    }
}
