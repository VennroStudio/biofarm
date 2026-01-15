<?php

declare(strict_types=1);

namespace App\Modules\Query\GetSpotifyTokens;

use Doctrine\DBAL\Connection;

final readonly class Fetcher
{
    public function __construct(
        private Connection $connection
    ) {}

    public function fetch(): array
    {
        $result = $this->connection->executeQuery('
            SELECT
                id,
                comment
            FROM
                spotify_token
        ');

        /** @var array{
         *     id: int,
         *     comment: string
         * }[] $rows
         */
        $rows = $result->fetchAllAssociative();

        $result = [];

        foreach ($rows as $row) {
            $result[] = new Result(
                id: $row['id'],
                comment: $row['comment']
            );
        }

        return $result;
    }
}
