<?php

declare(strict_types=1);

namespace App\Modules\Query\Artists\FindArtistTidal;

use App\Modules\Entity\Artist\Artist;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class Fetcher
{
    public function __construct(
        private Connection $connection
    ) {}

    /** @throws Exception */
    public function fetch(Query $query): ?ArtistResult
    {
        $result = $this->connection->createQueryBuilder()
            ->select(['id', 'tidal', 'union_id', 'checked_at'])
            ->from(Artist::DB_NAME)
            ->where('id = :id')
            ->setParameter('id', $query->id)
            ->executeQuery();

        /** @var array{
         *     id: int,
         *     union_id: int,
         *     tidal: string,
         *     checked_at: int|null
         * }|false $row
         */
        $row = $result->fetchAssociative();

        if (false === $row) {
            return null;
        }

        return new ArtistResult(
            id: $row['id'],
            tidal: $row['tidal'],
            unionId: $row['union_id'],
        );
    }
}
