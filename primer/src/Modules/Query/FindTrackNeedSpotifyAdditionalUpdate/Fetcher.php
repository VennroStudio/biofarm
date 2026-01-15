<?php

declare(strict_types=1);

namespace App\Modules\Query\FindTrackNeedSpotifyAdditionalUpdate;

use App\Modules\Constant;
use App\Modules\Entity\Track\Track;
use Doctrine\ORM\EntityManagerInterface;
use Throwable;

final readonly class Fetcher
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function fetch(?int $mod = null): ?Track
    {
        $queryBuilder = $this->em->createQueryBuilder();

        $queryBuilder
            ->select('t')
            ->from(Track::class, 't');

        if (null !== $mod) {
            $queryBuilder
                ->where('MOD(t.id, :countWorkers) = :mod')
                ->setParameter('countWorkers', Constant::SPOTIFY_ADDITIONAL_COUNT_WORKERS)
                ->setParameter('mod', $mod);
        }

        $queryBuilder
            ->andWhere('t.isApproved = 1')
            ->andWhere('t.spotifyAdditionalCheckedAt IS NULL')
            ->addOrderBy('t.id', 'ASC')
            ->setMaxResults(1);

        try {
            /** @var Track|null */
            return $queryBuilder->getQuery()->getOneOrNullResult();
        } catch (Throwable) {
        }

        return null;
    }
}
