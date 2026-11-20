<?php

declare(strict_types=1);

namespace App\Modules\Page\Query\Page\GetBySystemKey;

use App\Components\ReadModel\ReadModelFields;
use App\Modules\Page\Entity\Page\Page;
use App\Modules\Page\ReadModel\Page\PageDetails;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class PageGetBySystemKeyFetcher
{
    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * @throws Exception
     */
    public function fetch(PageGetBySystemKeyQuery $query): ?PageDetails
    {
        $row = $this->connection->createQueryBuilder()
            ->select(...ReadModelFields::select(PageDetails::fields(), 'p'))
            ->from('pages', 'p')
            ->where('p.page_type = :type')
            ->andWhere('p.system_key = :systemKey')
            ->andWhere('p.deleted_at IS NULL')
            ->setParameter('type', Page::TYPE_SYSTEM)
            ->setParameter('systemKey', $query->systemKey)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        return $row === false ? null : PageDetails::fromRow($row);
    }
}
