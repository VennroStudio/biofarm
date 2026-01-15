<?php

declare(strict_types=1);

namespace App\Modules\Query\Stats\Tracks\NotFoundApple;

use App\Modules\Entity\AppleTrack\AppleTrack;
use App\Modules\Entity\Track\Track;
use Doctrine\DBAL\Connection;

final readonly class Fetcher
{
    public function __construct(
        private Connection $connection
    ) {}

    public function fetch(Query $query): array
    {
        $sql = $this->connection->createQueryBuilder()
            ->select([
                'at.id',
                'at.apple_id', 'at.name', 'at.isrc', 'at.artists',
            ])
            ->from(AppleTrack::DB_NAME, 'at')
            ->leftJoin('at', Track::DB_NAME, 't', 't.apple_track_id = at.id')
            ->andWhere('t.apple_track_id IS NULL');

        if (null !== $query->albumId) {
            $sql
                ->andWhere('at.apple_album_id = (SELECT apple_album_id FROM albums WHERE id = :albumId)')
                ->setParameter('albumId', $query->albumId);
        }

        $result = $sql
            ->setMaxResults(10000)
            ->executeQuery();

        /** @var array{
         *     id: int,
         *     apple_id: int,
         *     name: string,
         *     isrc: string,
         *     artists: string,
         * }[] $rows
         */
        $rows = $result->fetchAllAssociative();

        $result = [];

        foreach ($rows as $row) {
            $result[] = [
                'id'            => $row['id'],
                'apple_id'      => $row['apple_id'],
                'name'          => $row['name'],
                'isrc'          => $row['isrc'],
                'artists'       => $row['artists'],
            ];
        }

        return $result;
    }
}
