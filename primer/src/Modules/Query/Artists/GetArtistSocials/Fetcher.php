<?php

declare(strict_types=1);

namespace App\Modules\Query\Artists\GetArtistSocials;

use App\Modules\Entity\ArtistSocial\ArtistSocial;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;

final readonly class Fetcher
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    /** @return ArtistSocial[] */
    public function fetch(Query $query): array
    {
        $criteria = Criteria::create();

        if (null !== $query->type) {
            $criteria->where(Criteria::expr()->eq('type', $query->type));
        }

        /** @var ArtistSocial[] $artistSocials */
        $artistSocials = $this->em->getRepository(ArtistSocial::class)
            ->matching(
                $criteria
                    ->andWhere(Criteria::expr()->eq('artistId', $query->artisId))
                    ->andWhere(Criteria::expr()->isNull('deletedAt'))
                    ->orderBy(['id' => 'ASC'])
            );

        $items = [];

        foreach ($artistSocials as $artistSocial) {
            $items[] = $artistSocial;
        }

        return $items;
    }
}
