<?php

declare(strict_types=1);

namespace App\Modules\Query\TrackProblematic;

use App\Modules\Query\TrackProblematic\Filter\TrackProblematicFilter;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class Fetcher
{
    public function __construct(
        private Connection $connection
    ) {}

    /**
     * @return list<array{id: int, lo_track_id: int, artist_id: int, name: string, artist_name: string, status: int, unionId: int, tidal_url: string|null, spotify_url: string|null}>
     * @throws Exception
     */
    public function fetch(Query $query): array
    {
        $qb = $this->connection->createQueryBuilder()
            ->select([
                'tp.id',
                'tp.lo_track_id',
                'tp.artist_id',
                'tp.track_name as name',
                'tp.artist_name',
                'tp.status',
                'tp.union_id as unionId',
                'tp.tidal_url',
                'tp.spotify_url',
            ])
            ->from('track_problematic', 'tp')
            ->setMaxResults($query->count)
            ->setFirstResult($query->offset);

        TrackProblematicFilter::apply($qb, $query);

        $qb->addOrderBy($query->field, $query->sort === 0 ? 'DESC' : 'ASC');

        $result = $qb->executeQuery();

        /** @var list<array{id: int, lo_track_id: int, artist_id: int, name: string, artist_name: string, status: int, unionId: int, tidal_url: string|null, spotify_url: string|null}> */
        return $result->fetchAllAssociative();
    }
}
