<?php

declare(strict_types=1);

namespace App\Modules\Query\ISRC\ISRCByAppleAlbum;

use App\Modules\Entity\AppleTrack\AppleTrack;
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
            ->from(AppleTrack::class, 't')
            ->andWhere('t.appleAlbumId = :albumId AND t.isrc IS NOT NULL')
            ->orderBy('t.id', 'ASC')
            ->setParameter('albumId', $albumId)
            ->setMaxResults(50);

        /** @var AppleTrack[] $tracks */
        $tracks = $queryBuilder->getQuery()->getResult();

        $result = [];

        foreach ($tracks as $track) {
            $isrc = $track->getIsrc();

            $result[] = $isrc;
        }

        return $result;
    }
}
