<?php

declare(strict_types=1);

namespace App\Modules\Query\Playlists\GetTranslatesArray;

use Doctrine\DBAL\Connection;

final readonly class Fetcher
{
    public function __construct(
        private Connection $connection
    ) {}

    public function fetch(Query $query): array
    {
        $result = $this->connection->executeQuery('
            SELECT
                id,
                lang,
                photo,
                name,
                description
            FROM
                playlist_translate
            WHERE
                playlist_id = :playlistId
        ', ['playlistId' => $query->playlistId]);

        /** @var array{
         *     id: int,
         *     lang: string,
         *     photo: ?string,
         *     name: string,
         *     description: ?string
         * }[] $rows
         */
        $rows = $result->fetchAllAssociative();

        $result = [];

        foreach ($rows as $row) {
            $result[] = new Result(
                id: $row['id'],
                lang: $row['lang'],
                name: $row['name'],
                photo: $row['photo'],
                description: $row['description'] ?? ''
            );
        }

        return $result;
    }
}
