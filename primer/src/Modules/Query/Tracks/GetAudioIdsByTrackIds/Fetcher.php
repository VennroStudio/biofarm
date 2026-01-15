<?php

declare(strict_types=1);

namespace App\Modules\Query\Tracks\GetAudioIdsByTrackIds;

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

        foreach ($query->trackIds as $trackId) {
            $id = $this->search($trackId);

            if ($id !== null) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /** @throws Exception */
    private function search(int $trackId): ?int
    {
        $result = $this->connection->createQueryBuilder()
            ->select(['lo_track_id'])
            ->from(Track::DB_NAME)
            ->andWhere('id = :trackId')
            ->setParameter('trackId', $trackId)
            ->executeQuery();

        /** @var array{
         *     lo_track_id: int|null,
         * }|false $row
         */
        $row = $result->fetchAssociative();

        if (false === $row) {
            return null;
        }

        return $row['lo_track_id'];
    }
}
