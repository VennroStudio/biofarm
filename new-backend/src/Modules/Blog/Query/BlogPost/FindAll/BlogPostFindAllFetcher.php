<?php

declare(strict_types=1);

namespace App\Modules\Blog\Query\BlogPost\FindAll;

use App\Components\Cacher\Cacher;
use App\Components\ReadModel\ModelCountItemsResult;
use App\Components\ReadModel\ReadModelFields;
use App\Modules\Blog\ReadModel\BlogPost\BlogPostDetails;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class BlogPostFindAllFetcher
{
    private const string TABLE = 'blog_posts';
    private const int CACHE_TTL = 900;

    public function __construct(
        private Connection $connection,
        private Cacher $cacher,
    ) {}

    /**
     * @return ModelCountItemsResult<BlogPostDetails>
     * @throws Exception
     */
    public function fetch(BlogPostFindAllQuery $query): ModelCountItemsResult
    {
        $key = $this->cacheKey($query);

        /** @var ModelCountItemsResult<BlogPostDetails>|null $cached */
        $cached = $this->cacher->get($key);
        if ($cached !== null) {
            return $cached;
        }

        $qb = $this->connection->createQueryBuilder()
            ->from(self::TABLE, 'bp')
            ->where('bp.deleted_at IS NULL');

        if ($query->onlyPublished) {
            $qb->andWhere('bp.is_published = 1');
        }

        $countQb = clone $qb;
        $total = (int)$countQb->select('COUNT(bp.id)')->executeQuery()->fetchOne();

        $rows = $qb
            ->select(...ReadModelFields::select(BlogPostDetails::fields(), 'bp'))
            ->orderBy('bp.created_at', 'DESC')
            ->setFirstResult($query->getOffset())
            ->setMaxResults($query->perPage)
            ->executeQuery()
            ->fetchAllAssociative();

        /** @var list<BlogPostDetails> $items */
        $items = BlogPostDetails::fromRows($rows);
        $result = new ModelCountItemsResult($items, $total);

        $this->cacher->setTagged($key, $result, self::CACHE_TTL, ['blog_posts']);

        return $result;
    }

    private function cacheKey(BlogPostFindAllQuery $query): string
    {
        return sprintf(
            'blog_posts_find_all_%s_%d_%d',
            $query->onlyPublished ? 'published' : 'any',
            $query->page,
            $query->perPage,
        );
    }
}
