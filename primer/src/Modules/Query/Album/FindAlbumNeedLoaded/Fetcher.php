<?php

declare(strict_types=1);

namespace App\Modules\Query\Album\FindAlbumNeedLoaded;

use App\Modules\Constant;
use App\Modules\Entity\Album\Album;
use App\Modules\Entity\AlbumArtist\AlbumArtist;
use App\Modules\Entity\Artist\Artist;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Throwable;

final readonly class Fetcher
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function fetch(?int $mod = null): ?AlbumArtist
    {
        $queryBuilder = $this->em->createQueryBuilder();

        $queryBuilder
            ->select('aa')
            ->from(AlbumArtist::class, 'aa')
            ->leftJoin(Album::class, 'album', Join::WITH, 'aa.albumId = album.id')
            ->leftJoin(Artist::class, 'artist', Join::WITH, 'aa.artistId = artist.id');

        if (null !== $mod) {
            $queryBuilder
                ->where('MOD(album.id, :countWorkers) = :mod')
                ->setParameter('countWorkers', Constant::LOADER_COUNT_WORKERS)
                ->setParameter('mod', $mod);
        }

        $queryBuilder
            ->andWhere('artist.mergedAt IS NOT NULL')
            ->andWhere('album.isApproved = 1')
            ->andWhere('album.allTracksMapped = 1')
            ->andWhere('aa.isLoaded = 0')
            ->orderBy('artist.priority', 'DESC')
            ->addOrderBy('artist.id', 'ASC')
            ->addOrderBy('album.spotifyReleasedAt', 'ASC')
            ->addOrderBy('album.id', 'ASC')
            ->setMaxResults(1);

        try {
            /** @var AlbumArtist|null */
            return $queryBuilder->getQuery()->getOneOrNullResult();
        } catch (Throwable) {
        }

        return null;
    }
}
