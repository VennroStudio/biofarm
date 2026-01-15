<?php

declare(strict_types=1);

namespace App\Modules\Command\Artist\UpdateStatsAlbums;

use Doctrine\DBAL\Connection;

final readonly class SpotifyHandler
{
    public function __construct(
        private Connection $connection
    ) {}

    public function handle(int $artistId): void
    {
        $sql = '
            UPDATE
                artist_stats s
            SET
                spotify_count_albums = (
                    SELECT
                        COUNT(id)
                    FROM
                        album_artists
                    WHERE
                        album_artists.artist_id = s.artist_id
                )
            WHERE
                s.artist_id = :artistId
        ';

        $this->connection->executeQuery($sql, ['artistId' => $artistId]);
    }
}
