<?php

declare(strict_types=1);

namespace App\Modules\Query\PossibleArtists\FindPossibleArtistNeedCheck;

use App\Modules\Constant;
use App\Modules\Entity\PossibleArtist\PossibleArtist;
use Doctrine\ORM\EntityManagerInterface;
use Throwable;

final readonly class Fetcher
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function fetch(): ?PossibleArtist
    {
        $queryBuilder = $this->em->createQueryBuilder();

        $queryBuilder
            ->select('artist')
            ->from(PossibleArtist::class, 'artist')
            ->andWhere('artist.artistId IS NULL')
            ->andWhere('artist.appleId IS NOT NULL')
            ->andWhere('artist.checkedAt IS NULL OR artist.checkedAt < :time')
            ->setParameter('time', Constant::timeFrom());

        $queryBuilder
            ->orderBy('artist.updatedAt', 'ASC')
            ->addOrderBy('artist.id', 'ASC')
            ->setMaxResults(1);

        try {
            /** @var PossibleArtist|null */
            return $queryBuilder->getQuery()->getOneOrNullResult();
        } catch (Throwable $e) {
            echo $e->getMessage();
        }

        return null;
    }
}
