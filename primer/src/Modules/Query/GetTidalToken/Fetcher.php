<?php

declare(strict_types=1);

namespace App\Modules\Query\GetTidalToken;

use App\Modules\Entity\TidalToken\TidalToken;
use Doctrine\DBAL\Connection;

final readonly class Fetcher
{
    public function __construct(
        private Connection $connection
    ) {}

    public function fetch(int $type): ?string
    {
        $result = $this->connection->createQueryBuilder()
            ->select(['id', 'access_token'])
            ->from(TidalToken::DB_NAME)
            ->andWhere('status = :status')
            ->andWhere('type = :type')
            ->setParameter('status', TidalToken::statusOn())
            ->setParameter('type', $type)
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
