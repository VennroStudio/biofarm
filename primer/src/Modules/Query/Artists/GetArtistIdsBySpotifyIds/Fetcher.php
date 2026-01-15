<?php

declare(strict_types=1);

namespace App\Modules\Query\Artists\GetArtistIdsBySpotifyIds;

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
            ->select(['artist_id'])
            ->from(ArtistSocial::DB_NAME)
            ->andWhere('type = :type')
            ->andWhere('url LIKE :spotifyId')
            ->setParameter('type', ArtistSocial::TYPE_SPOTIFY)
            ->setParameter('spotifyId', '%/' . $spotifyId . '%')
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
