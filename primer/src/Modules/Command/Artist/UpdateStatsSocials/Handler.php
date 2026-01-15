<?php

declare(strict_types=1);

namespace App\Modules\Command\Artist\UpdateStatsSocials;

use App\Modules\Entity\ArtistSocial\ArtistSocial;
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
                spotify_count_socials = (
                    SELECT
                        COUNT(*)
                    FROM
                        artist_socials
                    WHERE
                        artist_socials.artist_id = s.artist_id &&
                        artist_socials.type = ' . ArtistSocial::TYPE_SPOTIFY . ' &&
                        artist_socials.deleted_at IS NULL
                ),
                tidal_count_socials = (
                    SELECT
                        COUNT(*)
                    FROM
                        artist_socials
                    WHERE
                        artist_socials.artist_id = s.artist_id &&
                        artist_socials.type = ' . ArtistSocial::TYPE_TIDAL . ' &&
                        artist_socials.deleted_at IS NULL
                ),
                apple_count_socials = (
                    SELECT
                        COUNT(*)
                    FROM
                        artist_socials
                    WHERE
                        artist_socials.artist_id = s.artist_id &&
                        artist_socials.type = ' . ArtistSocial::TYPE_APPLE . ' &&
                        artist_socials.deleted_at IS NULL
                )
            WHERE
                s.artist_id = :artistId
        ';

        $this->connection->executeQuery($sql, ['artistId' => $artistId]);
    }
}
