<?php

declare(strict_types=1);

namespace App\Modules\Query\Artists\GetArtistIdsByTidalIds;

use App\Modules\Entity\ArtistSocial\ArtistSocial;
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

        foreach ($query->tidalIds as $tidalId) {
            $id = $this->search($tidalId);

            if ($id !== null) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /** @throws Exception */
    private function search(string $tidalId): ?int
    {
        $result = $this->connection->createQueryBuilder()
            ->select(['artist_id'])
            ->from(ArtistSocial::DB_NAME)
            ->andWhere('type = :type')
            ->andWhere('url LIKE :tidalId')
            ->setParameter('type', ArtistSocial::TYPE_TIDAL)
            ->setParameter('tidalId', '%/' . $tidalId . '%')
            ->executeQuery();

        /** @var array{
         *     artist_id: int,
         * }|false $row
         */
        $row = $result->fetchAssociative();

        if (false === $row) {
            return null;
        }

        return $row['artist_id'];
    }
}
