<?php

declare(strict_types=1);

namespace App\Modules\Query\GetSpotifySocialIds;

use App\Modules\Entity\ArtistSocial\ArtistSocial;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class Fetcher
{
    public function __construct(
        private Connection $connection
    ) {}

    /**
     * @return string[]
     * @throws Exception
     */
    public function fetch(Query $query): array
    {
        $sql = 'SELECT url FROM artist_socials WHERE artist_id = :artistId && type = :type && deleted_at IS NULL';

        $result = $this->connection->executeQuery($sql, [
            'artistId' => $query->artisId,
            'type' => ArtistSocial::TYPE_SPOTIFY,
        ]);

        /** @var array{ url: string}[] $rows */
        $rows = $result->fetchAllAssociative();

        $result = [];

        foreach ($rows as $row) {
            $arr = explode('/', $row['url']);
            $result[] = end($arr);
        }

        return $result;
    }
}
