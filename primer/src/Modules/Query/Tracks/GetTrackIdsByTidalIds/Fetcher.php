<?php

declare(strict_types=1);

namespace App\Modules\Query\Tracks\GetTrackIdsByTidalIds;

use App\Modules\Entity\TidalTrack\TidalTrack;
use App\Modules\Entity\Track\Track;
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
            ->select(['t.id'])
            ->from(Track::DB_NAME, 't')
            ->innerJoin('t', TidalTrack::DB_NAME, 'tt', 'tt.id = t.tidal_track_id')
            ->andWhere('tt.tidal_id = :tidalId')
            ->setParameter('tidalId', $tidalId)
            ->executeQuery();

        /** @var array{
         *     id: int,
         * }|false $row
         */
        $row = $result->fetchAssociative();

        if (false === $row) {
            return null;
        }

        return $row['id'];
    }
}
