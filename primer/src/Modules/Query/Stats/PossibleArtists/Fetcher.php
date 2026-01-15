<?php

declare(strict_types=1);

namespace App\Modules\Query\Stats\PossibleArtists;

use Doctrine\DBAL\Connection;

final readonly class Fetcher
{
    public function __construct(
        private Connection $connection,
        private Sql $sql,
    ) {}

    public function fetch(Query $query): array
    {
        $sql = $this->sql->get($query);

        $count = 50;
        $sql .= ' LIMIT ' . $query->offset . ', ' . $count;

        /** @var array{
         *     id: int,
         *     name: string,
         *     artist_id: int|null,
         *     playlist_id: int|null,
         *     spotify_id: string|null,
         *     apple_id: string|null,
         *     tidal_id: string|null,
         * }[] $rows
         */
        $rows = $this->connection
            ->executeQuery($sql)
            ->fetchAllAssociative();

        $result = [];

        foreach ($rows as $row) {
            $url = '';

            if (null !== $row['spotify_id']) {
                $url = 'https://open.spotify.com/artist/' . $row['spotify_id'];
            } elseif (null !== $row['apple_id']) {
                $url = 'https://music.apple.com/us/artist/' . $row['apple_id'];
            } elseif (null !== $row['tidal_id']) {
                $url = 'https://listen.tidal.com/artist/10312864' . $row['tidal_id'];
            }

            $result[] = [
                'id'            => $row['id'],
                'name'          => $row['name'],
                'artistId'      => $row['artist_id'],
                'playlistId'    => $row['playlist_id'],
                'spotifyId'     => $row['spotify_id'],
                'appleId'       => $row['apple_id'],
                'tidalId'       => $row['tidal_id'],
                'url'           => $url,
            ];
        }

        return $result;
    }
}
