<?php

declare(strict_types=1);

namespace App\Modules\Query\Stats\Playlists;

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
         *     priority: int,
         *     name: string,
         *     url: string,
         *     type: int,
         *     is_followed: int,
         *     checked_at: ?int,
         *     count_translates: int,
         *     count_translates_without_photo_ru: int,
         *     count_translates_without_photo_en: int,
         *     count_tracks: int,
         *     total_tracks: int,
         * }[] $rows
         */
        $rows = $this->connection
            ->executeQuery($sql)
            ->fetchAllAssociative();

        $result = [];

        foreach ($rows as $row) {
            $result[] = [
                'id'                                => $row['id'],
                'priority'                          => $row['priority'],
                'name'                              => $row['name'],
                'url'                               => $row['url'],
                'type'                              => $row['type'],
                'is_followed'                       => (bool)$row['is_followed'],
                'checked_at'                        => $row['checked_at'],
                'count_translates'                  => $row['count_translates'],
                'count_translates_without_photo_ru' => $row['count_translates_without_photo_ru'],
                'count_translates_without_photo_en' => $row['count_translates_without_photo_en'],
                'count_tracks'                      => $row['count_tracks'],
                'total_tracks'                      => $row['total_tracks'],
            ];
        }

        return $result;
    }
}
