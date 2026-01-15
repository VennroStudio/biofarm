<?php

declare(strict_types=1);

namespace App\Modules\Query\GetNextArtistTidal;

use App\Modules\Entity\Artist\Artist;
use App\Modules\Entity\ArtistSocial\ArtistSocial;
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
        $time = time() - 24 * 60 * 60;

        $sqlQuery = $this->connection->createQueryBuilder()
            ->select(['a.id', 's.url'])
            ->from(ArtistSocial::DB_NAME, 's')
            ->innerJoin('s', Artist::DB_NAME, 'a', 's.artist_id = a.id && s.type = 0')
            ->setParameter('time', $time);

        //        if ($query->mode === 1) {
        //            $sqlQuery->andWhere('a.checked_at IS NULL');
        //        } elseif ($query->mode === 2) {
        //            $sqlQuery->andWhere('a.checked_at IS NOT NULL');
        //        }

        if ($query->mode === 3) {
            $sqlQuery->andWhere('a.priority > 0');
        } else {
            $sqlQuery->andWhere('a.priority = 0');
        }

        $result = $sqlQuery
            ->orderBy('a.priority', 'ASC')
            ->addOrderBy('a.id', 'ASC')
            ->executeQuery();

        /** @var array{
         *     id: int,
         *     url: string,
         * }|false $row
         */
        $row = $result->fetchAssociative();

        if ($row === false) {
            return null;
        }

        $arr = explode('/', $row['url']);

        return new ArtistResult(
            id: $row['id'],
            artistId: end($arr),
        );
    }
}
