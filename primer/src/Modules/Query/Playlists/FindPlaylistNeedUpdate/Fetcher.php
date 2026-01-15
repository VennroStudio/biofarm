<?php

declare(strict_types=1);

namespace App\Modules\Query\Playlists\FindPlaylistNeedUpdate;

use App\Modules\Constant;
use App\Modules\Entity\Playlist\Playlist;
use Doctrine\ORM\EntityManagerInterface;
use Throwable;

final readonly class Fetcher
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function fetch(): ?Playlist
    {
        $queryBuilder = $this->em->createQueryBuilder();

        $queryBuilder
            ->select('playlist')
            ->from(Playlist::class, 'playlist')
            ->andWhere('
                (playlist.checkedAt IS NULL) OR
                (playlist.isFollowed = 1 AND playlist.checkedAt IS NOT NULL AND playlist.checkedAt < :time)
            ')
            ->andWhere('playlist.deletedAt IS NULL');

        $queryBuilder
            ->setParameter('time', Constant::timeFrom());

        $queryBuilder
            ->orderBy('playlist.priority', 'DESC')
            ->addOrderBy('playlist.checkedAt', 'ASC')
            ->addOrderBy('playlist.id', 'ASC')
            ->setMaxResults(1);

        try {
            /** @var Playlist|null */
            return $queryBuilder->getQuery()->getOneOrNullResult();
        } catch (Throwable $e) {
            echo $e->getMessage();
        }

        return null;
    }
}
