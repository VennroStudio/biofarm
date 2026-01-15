<?php

declare(strict_types=1);

namespace App\Modules\Query\GetSpotifyToken;

use App\Modules\Entity\SpotifyToken\SpotifyToken;
use Doctrine\DBAL\Connection;

final readonly class Fetcher
{
    public function __construct(
        private Connection $connection
    ) {}

    public function fetch(?int $tokenId = null): ?string
    {
        $query = $this->connection->createQueryBuilder()
            ->select(['id', 'access_token'])
            ->from(SpotifyToken::DB_NAME);

        if ($tokenId !== null) {
            $query
                ->andWhere('id = :id')
                ->setParameter('id', $tokenId);
        }

        $result = $query
            ->andWhere('status = :status')
            ->setParameter('status', SpotifyToken::statusOn())
            ->executeQuery();

        /** @var array{
         *     id: int,
         *     access_token: string
         * }|false $row
         */
        $row = $result->fetchAssociative();

        if (false === $row) {
            return null;
        }

        return $row['access_token'] ?? null;
    }
}
