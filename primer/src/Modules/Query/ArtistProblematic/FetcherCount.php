<?php

declare(strict_types=1);

namespace App\Modules\Query\ArtistProblematic;

use App\Modules\Query\ArtistProblematic\Filter\ArtistProblematicFilter;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class FetcherCount
{
    public function __construct(
        private Connection $connection
    ) {}

    /** @throws Exception */
    public function fetch(Query $query): int
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('COUNT(ap.id) as count')
            ->from('artist_problematic', 'ap');

        ArtistProblematicFilter::apply($qb, $query);

        /** @var array{count: int} $row */
        $row = $qb->executeQuery()->fetchAssociative();

        return $row['count'];
    }
}
