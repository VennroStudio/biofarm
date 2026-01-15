<?php

declare(strict_types=1);

namespace App\Modules\Query\ISRC\ISRCByTidalAlbum;

use App\Modules\Entity\TidalTrack\TidalTrack;
use Doctrine\ORM\EntityManagerInterface;

final readonly class Fetcher
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    /** @return string[] */
    public function fetch(int $albumId): array
    {
        $queryBuilder = $this->em->createQueryBuilder();

        $queryBuilder
            ->select('t')
            ->from(TidalTrack::class, 't')
            ->andWhere('t.tidalAlbumId = :albumId AND t.isrc IS NOT NULL')
            ->orderBy('t.id', 'ASC')
            ->setParameter('albumId', $albumId)
            ->setMaxResults(50);

        /** @var TidalTrack[] $tracks */
        $tracks = $queryBuilder->getQuery()->getResult();

        $result = [];

        foreach ($tracks as $track) {
            $isrc = $track->getIsrc();

            $result[] = $isrc;
        }

        return $result;
    }
}
