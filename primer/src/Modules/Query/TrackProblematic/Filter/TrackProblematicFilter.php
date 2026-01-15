<?php

declare(strict_types=1);

namespace App\Modules\Query\TrackProblematic\Filter;

use App\Modules\Query\TrackProblematic\Query;
use Doctrine\DBAL\Query\QueryBuilder;

final class TrackProblematicFilter
{
    public static function apply(QueryBuilder $qb, Query $query): void
    {
        if ($query->search !== null) {
            $qb->andWhere('LOWER(tp.track_name) LIKE :search OR LOWER(tp.artist_name) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($query->search) . '%');
        }

        if ($query->status !== null) {
            $qb->andWhere('tp.status = :status')
                ->setParameter('status', $query->status);
        }

        if ($query->artist !== null) {
            if ($query->artist === 0) {
                $qb->andWhere('tp.artist_id = 0');
            } elseif ($query->artist === 1) {
                $qb->andWhere('tp.artist_id > 0');
            }
        }
    }
}
