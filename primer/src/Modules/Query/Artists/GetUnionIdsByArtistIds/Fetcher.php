<?php

declare(strict_types=1);

namespace App\Modules\Query\Artists\GetUnionIdsByArtistIds;

use App\Modules\Entity\Artist\Artist;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class Fetcher
{
    public function __construct(
        private Connection $connection
    ) {}

    /**
     * @return int[]
     * @throws Exception
     */
    public function fetch(Query $query): array
    {
        $ids = [];

        foreach ($query->artistIds as $artistId) {
            $id = $this->search($artistId);

            if ($id !== null) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /** @throws Exception */
    private function search(int $artistId): ?int
    {
        $result = $this->connection->createQueryBuilder()
            ->select(['union_id'])
            ->from(Artist::DB_NAME)
            ->andWhere('id = :id')
            ->setParameter('id', $artistId)
            ->executeQuery();

        /** @var array{
         *     union_id: int,
         * }|false $row
         */
        $row = $result->fetchAssociative();

        if (false === $row) {
            return null;
        }

        return $row['union_id'];
    }
}
