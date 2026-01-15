<?php

declare(strict_types=1);

namespace App\Modules\Query\Artists\GetUnionIdsBySpotifyIds;

use App\Modules\Entity\Artist\Artist;
use App\Modules\Entity\ArtistSocial\ArtistSocial;
use Doctrine\DBAL\Connection;
use Exception;

final readonly class Fetcher
{
    public function __construct(
        private Connection $connection
    ) {}

    /**
     * @return array{unionId: int, url: string}[]
     * @throws Exception
     */
    public function fetch(Query $query): array
    {
        $result = [];

        foreach ($query->spotifyIds as $spotifyId) {
            $data = $this->search($spotifyId);

            if ($data !== null) {
                $result[] = $data;
            }
        }

        return $result;
    }

    /**
     * @return array{unionId: int, url: string}|null
     * @throws Exception
     */
    private function search(string $spotifyId): ?array
    {
        $result = $this->connection->createQueryBuilder()
            ->select(['a.union_id', 'social.url'])
            ->from(ArtistSocial::DB_NAME, 'social')
            ->innerJoin('social', Artist::DB_NAME, 'a', 'social.artist_id = a.id')
            ->andWhere('social.type = :type')
            ->andWhere('social.url LIKE :spotifyId')
            ->setParameter('type', ArtistSocial::TYPE_SPOTIFY)
            ->setParameter('spotifyId', '%/' . $spotifyId . '%')
            ->executeQuery();

        /** @var array{
         *     union_id: int,
         *     url: string,
         * }|false $row
         */
        $row = $result->fetchAssociative();

        if (false === $row) {
            return null;
        }

        return [
            'unionId' => $row['union_id'],
            'url'     => $row['url'],
        ];
    }
}
