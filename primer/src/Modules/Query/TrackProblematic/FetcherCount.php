<?php

declare(strict_types=1);

namespace App\Modules\Query\TrackProblematic;

use App\Modules\Query\TrackProblematic\Filter\TrackProblematicFilter;
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
            ->select('COUNT(tp.id) as count')
            ->from('track_problematic', 'tp');

        TrackProblematicFilter::apply($qb, $query);

        /** @var array{count: int} $row */
        $row = $qb->executeQuery()->fetchAssociative();

        return $row['count'];
    }
}
