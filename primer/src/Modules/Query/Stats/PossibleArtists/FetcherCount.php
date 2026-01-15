<?php

declare(strict_types=1);

namespace App\Modules\Query\Stats\PossibleArtists;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class FetcherCount
{
    public function __construct(
        private Connection $connection,
        private Sql $sql,
    ) {}

    /** @throws Exception */
    public function fetch(Query $query): int
    {
        return $this->connection->executeQuery(
            $this->sql->get($query)
        )->rowCount();
    }
}
