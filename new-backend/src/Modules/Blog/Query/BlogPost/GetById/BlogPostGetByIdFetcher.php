<?php

declare(strict_types=1);

namespace App\Modules\Blog\Query\BlogPost\GetById;

use App\Components\Cacher\Cacher;
use App\Components\Exception\DomainExceptionModule;
use App\Components\ReadModel\ReadModelFields;
use App\Modules\Blog\ReadModel\BlogPost\BlogPostDetails;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class BlogPostGetByIdFetcher
{
    private const string TABLE = 'blog_posts';
    private const int CACHE_TTL = 900;

    public function __construct(
        private Connection $connection,
        private Cacher $cacher,
    ) {}

    /**
     * @throws Exception
     */
    public function fetch(BlogPostGetByIdQuery $query): BlogPostDetails
    {
        $key = 'blog_post_by_id_' . $query->id . '_' . ($query->onlyPublished ? 'published' : 'any');

        /** @var BlogPostDetails|null $cached */
        $cached = $this->cacher->get($key);
        if ($cached !== null) {
            return $cached;
        }

        $qb = $this->connection->createQueryBuilder()
            ->select(...ReadModelFields::select(BlogPostDetails::fields(), 'bp'))
            ->from(self::TABLE, 'bp')
            ->where('bp.id = :id')
            ->andWhere('bp.deleted_at IS NULL')
            ->setParameter('id', $query->id)
            ->setMaxResults(1);

        if ($query->onlyPublished) {
            $qb->andWhere('bp.is_published = 1');
        }

        $row = $qb->executeQuery()->fetchAssociative();

        if ($row === false) {
            throw new DomainExceptionModule(
                module: 'blog',
                message: 'error.blog_post_not_found',
                code: 1,
            );
        }

        /** @var array{id: int|string, slug: string, title: string, excerpt: string, content: string, image: string, category_id: string, author_name: string, read_time: int|string, is_published: int|string|bool, created_at: string, updated_at: string|null} $row */
        $post = BlogPostDetails::fromRow($row);
        $this->cacher->setTagged($key, $post, self::CACHE_TTL, ['blog_posts']);

        return $post;
    }
}
