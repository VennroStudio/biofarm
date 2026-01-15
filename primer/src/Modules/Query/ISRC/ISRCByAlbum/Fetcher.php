<?php

declare(strict_types=1);

namespace App\Modules\Query\ISRC\ISRCByAlbum;

use App\Modules\Entity\Track\Track;
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
            ->from(Track::class, 't')
            ->andWhere('t.albumId = :albumId AND t.spotifyISRC IS NOT NULL')
            ->orderBy('t.id', 'ASC')
            ->setParameter('albumId', $albumId)
            ->setMaxResults(50);

        /** @var Track[] $tracks */
        $tracks = $queryBuilder->getQuery()->getResult();

        $result = [];

        foreach ($tracks as $track) {
            $isrc = $track->getSpotifyISRC();

            if (null !== $isrc) {
                $result[] = $isrc;
            }
        }

        return $result;
    }
}
