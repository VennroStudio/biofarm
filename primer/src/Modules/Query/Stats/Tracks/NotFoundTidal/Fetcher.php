<?php

declare(strict_types=1);

namespace App\Modules\Query\Stats\Tracks\NotFoundTidal;

use App\Modules\Entity\TidalTrack\TidalTrack;
use App\Modules\Entity\Track\Track;
use Doctrine\DBAL\Connection;

final readonly class Fetcher
{
    public function __construct(
        private Connection $connection
    ) {}

    public function fetch(Query $query): array
    {
        $sql = $this->connection->createQueryBuilder()
            ->select([
                'tt.id',
                'tt.tidal_id', 'tt.name', 'tt.isrc', 'tt.artists',
            ])
            ->from(TidalTrack::DB_NAME, 'tt')
            ->leftJoin('tt', Track::DB_NAME, 't', 't.tidal_track_id = tt.id')
            ->andWhere('t.tidal_track_id IS NULL');

        if (null !== $query->albumId) {
            $sql
                ->andWhere('tt.tidal_album_id = (SELECT tidal_album_id FROM albums WHERE id = :albumId)')
                ->setParameter('albumId', $query->albumId);
        }

        $result = $sql
            ->setMaxResults(10000)
            ->executeQuery();

        /** @var array{
         *     id: int,
         *     tidal_id: int,
         *     name: string,
         *     isrc: string,
         *     artists: string,
         * }[] $rows
         */
        $rows = $result->fetchAllAssociative();

        $result = [];

        foreach ($rows as $row) {
            $tidalArtists = [];

            /** @var array{name: string}[] $tidalArtistsTemp */
            $tidalArtistsTemp = json_decode($row['artists'] ?? '[]', true) ?? [];
            foreach ($tidalArtistsTemp as $tidalArtist) {
                $tidalArtists[] = $tidalArtist['name'];
            }

            $result[] = [
                'id'            => $row['id'],
                'tidal_id'      => $row['tidal_id'],
                'name'          => $row['name'],
                'isrc'          => $row['isrc'],
                'artists'       => implode(', ', $tidalArtists),
            ];
        }

        return $result;
    }
}
