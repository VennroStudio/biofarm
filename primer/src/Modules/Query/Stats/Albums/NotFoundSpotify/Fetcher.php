<?php

declare(strict_types=1);

namespace App\Modules\Query\Stats\Albums\NotFoundSpotify;

use App\Modules\Entity\Album\Album;
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
                'a.id',
                'a.spotify_id', 'a.spotify_name', 'a.spotify_total_tracks', 'a.spotify_type', 'a.spotify_released_at', 'a.spotify_upc', 'a.spotify_artists',
            ])
            ->from(Album::DB_NAME, 'a')
            ->andWhere('a.tidal_album_id IS NULL');

        if (null !== $query->artistId) {
            $sql
                ->andWhere('a.id IN(SELECT album_id FROM album_artists WHERE artist_id = :artistId)')
                ->setParameter('artistId', $query->artistId);
        }

        $result = $sql
            ->orderBy('a.spotify_released_at', 'DESC')
            ->setMaxResults(10000)
            ->executeQuery();

        /** @var array{
         *     id: int,
         *     spotify_id: string,
         *     spotify_name: string,
         *     spotify_total_tracks: int,
         *     spotify_type: string,
         *     spotify_released_at: int,
         *     spotify_upc: string,
         *     spotify_artists: string,
         * }[] $rows
         */
        $rows = $result->fetchAllAssociative();

        $result = [];

        foreach ($rows as $row) {
            $spotifyArtists = [];

            /** @var array{name: string}[] $spotifyArtistsTemp */
            $spotifyArtistsTemp = json_decode($row['spotify_artists'] ?? '[]', true) ?? [];
            foreach ($spotifyArtistsTemp as $spotifyArtist) {
                $spotifyArtists[] = $spotifyArtist['name'];
            }

            $result[] = [
                'id'            => $row['id'],
                'spotify_id'    => $row['spotify_id'],
                'name'          => $row['spotify_name'],
                'total_tracks'  => $row['spotify_total_tracks'],
                'type'          => strtoupper($row['spotify_type']),
                'released_at'   => $row['spotify_released_at'],
                'upc'           => $row['spotify_upc'],
                'artists'       => implode(', ', $spotifyArtists),
            ];
        }

        return $result;
    }
}
