<?php

declare(strict_types=1);

namespace App\Modules\Query\FindSpotifyTokenNeedRefresh;

use App\Modules\Entity\SpotifyToken\SpotifyToken;
use Doctrine\ORM\EntityManagerInterface;
use Throwable;

final readonly class Fetcher
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function fetch(): ?SpotifyToken
    {
        $queryBuilder = $this->em->createQueryBuilder();

        $queryBuilder
            ->select('t')
            ->from(SpotifyToken::class, 't')
            ->andWhere('t.cookies IS NOT NULL')
            ->andWhere('t.updatedAt <= :time')
            ->setParameter('time', time() - 20 * 60);

        $queryBuilder
            ->orderBy('t.status', 'DESC')
            ->addOrderBy('t.updatedAt', 'ASC')
            ->addOrderBy('t.id', 'ASC')
            ->setMaxResults(1);

        try {
            /** @var SpotifyToken|null */
            return $queryBuilder->getQuery()->getOneOrNullResult();
        } catch (Throwable) {
        }

        return null;
    }
}
