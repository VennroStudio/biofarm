<?php

declare(strict_types=1);

namespace App\Modules\Query\Artists\GetArtistSocialsArray;

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
                type,
                url,
                description
            FROM
                artist_socials
            WHERE
                artist_id = :artistId &&
                deleted_at IS NULL
        ', ['artistId' => $query->artisId]);

        /** @var array{
         *     id: int,
         *     type: int,
         *     url: string,
         *     description: ?string
         * }[] $rows
         */
        $rows = $result->fetchAllAssociative();

        $result = [];

        foreach ($rows as $row) {
            $result[] = new Result(
                id: $row['id'],
                type: $row['type'],
                url: $row['url'],
                description: $row['description'] ?? ''
            );
        }

        return $result;
    }
}
