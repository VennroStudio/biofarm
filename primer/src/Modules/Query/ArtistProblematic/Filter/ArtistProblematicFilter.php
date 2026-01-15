<?php

declare(strict_types=1);

namespace App\Modules\Query\ArtistProblematic\Filter;

use App\Modules\Query\ArtistProblematic\Query;
use Doctrine\DBAL\Query\QueryBuilder;

final class ArtistProblematicFilter
{
    public static function apply(QueryBuilder $qb, Query $query): void
    {
        if ($query->search !== null) {
            $qb->andWhere('LOWER(ap.artist_name) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($query->search) . '%');
        }

        if ($query->status !== null) {
            $qb->andWhere('ap.status = :status')
                ->setParameter('status', $query->status);
        }
    }
}
