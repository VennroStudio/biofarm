<?php

declare(strict_types=1);

namespace App\Modules\Query\GetAlbumArtists;

use App\Modules\Entity\AlbumArtist\AlbumArtist;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;

final readonly class Fetcher
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    /** @return AlbumArtist[] */
    public function fetch(Query $query): array
    {
        $criteria = Criteria::create();

        /** @var AlbumArtist[] $albumArtists */
        $albumArtists = $this->em->getRepository(AlbumArtist::class)
            ->matching(
                $criteria
                    ->andWhere(Criteria::expr()->eq('albumId', $query->albumId))
                    ->orderBy(['id' => 'ASC'])
            );

        $items = [];

        foreach ($albumArtists as $albumArtist) {
            $items[] = $albumArtist;
        }

        return $items;
    }
}
