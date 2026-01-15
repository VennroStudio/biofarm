<?php

declare(strict_types=1);

namespace App\Modules\Query\Stats\Tracks\NotFoundSpotify;

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
                't.id',
                't.spotify_id', 't.spotify_name', 't.spotify_isrc', 't.spotify_artists',
            ])
            ->from(Track::DB_NAME, 't')
            ->andWhere('t.tidal_track_id IS NULL');

        if (null !== $query->albumId) {
            $sql
                ->andWhere('t.album_id = :albumId')
                ->setParameter('albumId', $query->albumId);
        }

        $result = $sql
            ->setMaxResults(10000)
            ->executeQuery();

        /** @var array{
         *     id: int,
         *     spotify_id: string,
         *     spotify_name: string,
         *     spotify_isrc: string,
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
                'isrc'          => $row['spotify_isrc'],
                'artists'       => implode(', ', $spotifyArtists),
            ];
        }

        return $result;
    }
}
