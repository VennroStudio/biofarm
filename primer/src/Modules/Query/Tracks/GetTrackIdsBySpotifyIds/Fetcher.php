<?php

declare(strict_types=1);

namespace App\Modules\Query\Tracks\GetTrackIdsBySpotifyIds;

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

        foreach ($query->spotifyIds as $spotifyId) {
            $id = $this->search($spotifyId);

            if ($id !== null) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /** @throws Exception */
    private function search(string $spotifyId): ?int
    {
        $result = $this->connection->createQueryBuilder()
            ->select(['id'])
            ->from(Track::DB_NAME)
            ->andWhere('spotify_id = :spotifyId')
            ->setParameter('spotifyId', $spotifyId)
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
