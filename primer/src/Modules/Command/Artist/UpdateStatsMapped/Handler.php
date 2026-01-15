<?php

declare(strict_types=1);

namespace App\Modules\Command\Artist\UpdateStatsMapped;

use Doctrine\DBAL\Connection;

final readonly class Handler
{
    public function __construct(
        private Connection $connection
    ) {}

    public function handle(int $artistId): void
    {
        $this->updateCountApproved($artistId);
        $this->updateCountApprovedWithTracks($artistId);
        $this->updateCountConflicts($artistId);
    }

    public function updateCountApproved(int $artistId): void
    {
        $sql = '
            UPDATE
                artist_stats s
            SET
                count_approved = (
                    SELECT
                        COUNT(album_artists.id)
                    FROM
                        album_artists INNER JOIN albums ON albums.id = album_artists.album_id AND album_artists.artist_id = s.artist_id
                    WHERE
                        albums.tidal_album_id IS NOT NULL &&
                        albums.is_approved = 1
                )
            WHERE
                s.artist_id = :artistId
        ';

        $this->connection->executeQuery($sql, ['artistId' => $artistId]);
    }

    public function updateCountApprovedWithTracks(int $artistId): void
    {
        $sql = '
            UPDATE
                artist_stats s
            SET
                count_approved_with_tracks = (
                    SELECT
                        COUNT(album_artists.id)
                    FROM
                        album_artists INNER JOIN albums ON albums.id = album_artists.album_id AND album_artists.artist_id = s.artist_id
                    WHERE
                        albums.all_tracks_mapped = 1
                )
            WHERE
                s.artist_id = :artistId
        ';

        $this->connection->executeQuery($sql, ['artistId' => $artistId]);
    }

    public function updateCountConflicts(int $artistId): void
    {
        $sql = '
            UPDATE
                artist_stats s
            SET
                count_conflicts = (
                    SELECT
                        COUNT(album_artists.id)
                    FROM
                        album_artists INNER JOIN albums ON albums.id = album_artists.album_id AND album_artists.artist_id = s.artist_id
                    WHERE
                        albums.tidal_album_id IS NOT NULL &&
                        albums.is_approved = 0
                )
            WHERE
                s.artist_id = :artistId
        ';

        $this->connection->executeQuery($sql, ['artistId' => $artistId]);
    }
}
