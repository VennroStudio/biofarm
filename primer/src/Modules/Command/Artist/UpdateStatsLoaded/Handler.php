<?php

declare(strict_types=1);

namespace App\Modules\Command\Artist\UpdateStatsLoaded;

use Doctrine\DBAL\Connection;

final readonly class Handler
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
                count_loaded = (
                    SELECT
                        COUNT(DISTINCT album_id)
                    FROM
                        album_artists
                    WHERE
                        is_loaded = 1 AND
                        artist_id = s.artist_id
                )
            WHERE
                s.artist_id = :artistId
        ';

        $this->connection->executeQuery($sql, ['artistId' => $artistId]);
    }
}
