<?php

declare(strict_types=1);

namespace App\Modules\Query\Stats\Artists;

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
         *     description: string,
         *     lo_name: string,
         *     lo_description: string,
         *     lo_category_id: int,
         *     priority: int,
         *     union_id: int,
         *     spotify_checked_at: ?int,
         *     tidal_checked_at: ?int,
         *     apple_checked_at: ?int,
         *     merged_at: ?int,
         *     checked_at: ?int,
         *     spotify_count_albums: int,
         *     spotify_count_socials: int,
         *     tidal_count_albums: int,
         *     tidal_count_socials: int,
         *     apple_count_albums: int,
         *     apple_count_socials: int,
         *     count_approved: int,
         *     count_approved_with_tracks: int,
         *     count_conflicts: int,
         *     count_loaded: int,
         * }[] $rows
         */
        $rows = $this->connection
            ->executeQuery($sql)
            ->fetchAllAssociative();

        $result = [];

        foreach ($rows as $row) {
            $val = $row['count_approved'] - $row['count_conflicts'];

            $result[] = [
                'id'                            => $row['id'],
                'name'                          => $row['description'],
                'lo_name'                       => $row['lo_name'],
                'lo_description'                => $row['lo_description'],
                'lo_category_id'                => $row['lo_category_id'],
                'priority'                      => $row['priority'],
                'union_id'                      => $row['union_id'],
                'spotify_checked_at'            => $row['spotify_checked_at'],
                'tidal_checked_at'              => $row['tidal_checked_at'],
                'apple_checked_at'              => $row['apple_checked_at'],
                'merged_at'                     => $row['merged_at'],
                'checked_at'                    => $row['checked_at'],
                'spotify_count_albums'          => $row['spotify_count_albums'],
                'spotify_count_socials'         => $row['spotify_count_socials'],
                'tidal_count_albums'            => $row['tidal_count_albums'],
                'tidal_count_socials'           => $row['tidal_count_socials'],
                'apple_count_albums'            => $row['apple_count_albums'],
                'apple_count_socials'           => $row['apple_count_socials'],
                'count_approved'                => $row['count_approved'],
                'count_approved_with_tracks'    => $row['count_approved_with_tracks'],
                'count_conflicts'               => $row['count_conflicts'],
                'count_loaded'                  => $row['count_loaded'],
                'spotify_free'                  => ($row['spotify_count_albums'] > $val) ? $row['spotify_count_albums'] - $val : 0,
                'tidal_free'                    => ($row['tidal_count_albums'] > $val) ? $row['tidal_count_albums'] - $val : 0,
                'apple_free'                    => ($row['apple_count_albums'] > $val) ? $row['apple_count_albums'] - $val : 0,
            ];
        }

        return $result;
    }
}
