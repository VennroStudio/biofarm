<?php

declare(strict_types=1);

namespace App\Modules\Query\Stats\Albums\NotFoundApple;

use App\Modules\Entity\Album\Album;
use App\Modules\Entity\AppleAlbum\AppleAlbum;
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
                'aa.id',
                'aa.apple_id', 'aa.name', 'aa.total_tracks', 'aa.is_compilation', 'aa.is_single', 'aa.released_at', 'aa.upc', 'aa.artists',
            ])
            ->from(AppleAlbum::DB_NAME, 'aa')
            ->leftJoin('aa', Album::DB_NAME, 'a', 'a.apple_album_id = aa.id')
            ->andWhere('a.apple_album_id IS NULL');

        if (null !== $query->artistId) {
            $sql
                ->andWhere('aa.id IN(SELECT album_id FROM apple_album_artists WHERE artist_id = :artistId)')
                ->setParameter('artistId', $query->artistId);
        }

        $result = $sql
            ->orderBy('aa.released_at', 'DESC')
            ->setMaxResults(10000)
            ->executeQuery();

        /** @var array{
         *     id: int,
         *     apple_id: int,
         *     is_compilation: int,
         *     is_single: int,
         *     name: string,
         *     total_tracks: int,
         *     released_at: ?int,
         *     upc: string,
         *     artists: string,
         * }[] $rows
         */
        $rows = $result->fetchAllAssociative();

        $result = [];

        foreach ($rows as $row) {
            $type = 'ALBUM';

            if ($row['is_compilation'] === 1) {
                $type = 'COMPILATION';
            } elseif ($row['is_single'] === 1) {
                $type = 'SINGLE';
            }

            $result[] = [
                'id'            => $row['id'],
                'apple_id'      => $row['apple_id'],
                'type'          => $type,
                'released_at'   => $row['released_at'],
                'name'          => $row['name'],
                'total_tracks'  => $row['total_tracks'],
                'upc'           => $row['upc'],
                'artists'       => $row['artists'],
            ];
        }

        return $result;
    }
}
