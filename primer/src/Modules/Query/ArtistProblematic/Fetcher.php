<?php

declare(strict_types=1);

namespace App\Modules\Query\ArtistProblematic;

use App\Modules\Query\ArtistProblematic\Filter\ArtistProblematicFilter;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class Fetcher
{
    public function __construct(
        private Connection $connection
    ) {}

    /**
     * @return list<array{id: int, artist_id: int, artist_name: string, status: int, tidal_url: string|null, spotify_url: string|null}>
     * @throws Exception
     */
    public function fetch(Query $query): array
    {
        $qb = $this->connection->createQueryBuilder()
            ->select([
                'ap.id',
                'ap.artist_id',
                'ap.artist_name',
                'ap.status',
                'ap.tidal_url',
                'ap.spotify_url',
            ])
            ->from('artist_problematic', 'ap')
            ->setMaxResults($query->count)
            ->setFirstResult($query->offset);

        ArtistProblematicFilter::apply($qb, $query);

        $qb->addOrderBy($query->field, $query->sort === 0 ? 'DESC' : 'ASC');

        $result = $qb->executeQuery();

        /** @var list<array{id: int, artist_id: int, artist_name: string, status: int, tidal_url: string|null, spotify_url: string|null}> */
        return $result->fetchAllAssociative();
    }
}
