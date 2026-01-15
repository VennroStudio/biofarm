<?php

declare(strict_types=1);

namespace App\Modules\Query\Stats\Albums\NotFoundTidal;

use App\Modules\Entity\Album\Album;
use App\Modules\Entity\TidalAlbum\TidalAlbum;
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
                'ta.id',
                'ta.tidal_id', 'ta.name', 'ta.total_tracks', 'ta.type', 'ta.released_at', 'ta.barcode_id', 'ta.artists',
            ])
            ->from(TidalAlbum::DB_NAME, 'ta')
            ->leftJoin('ta', Album::DB_NAME, 'a', 'a.tidal_album_id = ta.id')
            ->andWhere('a.tidal_album_id IS NULL');

        if (null !== $query->artistId) {
            $sql
                ->andWhere('ta.id IN(SELECT album_id FROM tidal_album_artists WHERE artist_id = :artistId)')
                ->setParameter('artistId', $query->artistId);
        }

        $result = $sql
            ->orderBy('ta.released_at', 'DESC')
            ->setMaxResults(10000)
            ->executeQuery();

        /** @var array{
         *     id: int,
         *     tidal_id: int,
         *     type: string,
         *     name: string,
         *     total_tracks: int,
         *     released_at: ?int,
         *     barcode_id: string,
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
                'type'          => $row['type'],
                'released_at'   => $row['released_at'],
                'name'          => $row['name'],
                'total_tracks'  => $row['total_tracks'],
                'upc'           => $row['barcode_id'],
                'artists'       => implode(', ', $tidalArtists),
            ];
        }

        return $result;
    }
}
