<?php

declare(strict_types=1);

namespace App\Modules\Query\FindArtistSocialNeedSimilarUpdate;

use App\Modules\Entity\ArtistSocial\ArtistSocial;
use Doctrine\ORM\EntityManagerInterface;
use Throwable;

final readonly class Fetcher
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function fetch(): ?ArtistSocial
    {
        $queryBuilder = $this->em->createQueryBuilder();

        $queryBuilder
            ->select('artist_socials')
            ->from(ArtistSocial::class, 'artist_socials')
            ->andWhere('artist_socials.rateCheckedAt IS NULL')
            ->andWhere('artist_socials.type = 0 OR artist_socials.type = 1');

        $queryBuilder
            ->orderBy('artist_socials.artistId', 'ASC')
            ->addOrderBy('artist_socials.id', 'ASC')
            ->setMaxResults(1);

        try {
            /** @var ArtistSocial|null */
            return $queryBuilder->getQuery()->getOneOrNullResult();
        } catch (Throwable) {
        }

        return null;
    }
}
