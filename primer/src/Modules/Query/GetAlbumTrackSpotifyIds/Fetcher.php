<?php

declare(strict_types=1);

namespace App\Modules\Query\GetAlbumTrackSpotifyIds;

use App\Modules\Entity\Track\Track;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;

final readonly class Fetcher
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    /** @return string[] */
    public function fetch(Query $query): array
    {
        $criteria = Criteria::create();

        /** @var Track[] $tracks */
        $tracks = $this->em->getRepository(Track::class)
            ->matching(
                $criteria
                    ->andWhere(Criteria::expr()->eq('albumId', $query->albumId))
                    ->orderBy(['id' => 'ASC'])
            );

        $items = [];

        foreach ($tracks as $track) {
            $items[] = $track->getSpotifyId();
        }

        return $items;
    }
}
