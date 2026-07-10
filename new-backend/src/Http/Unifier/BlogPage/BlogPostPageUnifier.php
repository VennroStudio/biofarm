<?php

declare(strict_types=1);

namespace App\Http\Unifier\BlogPage;

use App\Http\View\Blog\BlogPostPageView;
use App\Http\View\Blog\BlogPostView;
use App\Http\View\PageMetaView;
use Doctrine\DBAL\Connection;

final readonly class BlogPostPageUnifier
{
    private const array MONTHS = [
        1 => 'января',
        2 => 'февраля',
        3 => 'марта',
        4 => 'апреля',
        5 => 'мая',
        6 => 'июня',
        7 => 'июля',
        8 => 'августа',
        9 => 'сентября',
        10 => 'октября',
        11 => 'ноября',
        12 => 'декабря',
    ];

    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function unify(string $slug): BlogPostPageView
    {
        $post = $this->post($slug);

        return new BlogPostPageView(
            meta: new PageMetaView(
                title: $post !== null ? $post->title . ' — БИОФАРМ' : 'Статья — БИОФАРМ',
                description: $post?->excerpt ?? 'Материал блога БИОФАРМ.',
            ),
            post: $post,
            relatedPosts: $post !== null ? $this->relatedPosts($post) : [],
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function post(string $slug): ?BlogPostView
    {
        $row = $this->connection->createQueryBuilder()
            ->select(
                'bp.id',
                'bp.slug',
                'bp.title',
                'bp.excerpt',
                'bp.content',
                'bp.image',
                'bp.category_id',
                'bp.created_at',
                'bp.author_name',
                'bp.read_time',
            )
            ->from('blog_posts', 'bp')
            ->where('bp.slug = :slug')
            ->andWhere('bp.deleted_at IS NULL')
            ->andWhere('bp.is_published = 1')
            ->setParameter('slug', $slug)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if ($row === false) {
            return null;
        }

        return $this->mapPost($row);
    }

    /**
     * @return list<BlogPostView>
     * @throws \Doctrine\DBAL\Exception
     */
    private function relatedPosts(BlogPostView $post): array
    {
        /** @var list<array{id: int|string, slug: string, title: string, excerpt: string, content: string, image: string, category_id: string, created_at: string, author_name: string|null, read_time: int|string}> $rows */
        $rows = $this->connection->createQueryBuilder()
            ->select(
                'bp.id',
                'bp.slug',
                'bp.title',
                'bp.excerpt',
                'bp.content',
                'bp.image',
                'bp.category_id',
                'bp.created_at',
                'bp.author_name',
                'bp.read_time',
            )
            ->from('blog_posts', 'bp')
            ->where('bp.slug != :slug')
            ->andWhere('bp.category_id = :category')
            ->andWhere('bp.deleted_at IS NULL')
            ->andWhere('bp.is_published = 1')
            ->setParameter('slug', $post->slug)
            ->setParameter('category', $post->category)
            ->orderBy('bp.created_at', 'DESC')
            ->addOrderBy('bp.id', 'DESC')
            ->setMaxResults(3)
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map($this->mapPost(...), $rows);
    }

    /**
     * @param array{id: int|string, slug: string, title: string, excerpt: string, content: string, image: string, category_id: string, created_at: string, author_name: string|null, read_time: int|string} $row
     */
    private function mapPost(array $row): BlogPostView
    {
        return new BlogPostView(
            id: (int)$row['id'],
            slug: (string)$row['slug'],
            title: (string)$row['title'],
            excerpt: (string)$row['excerpt'],
            content: (string)$row['content'],
            image: (string)$row['image'],
            category: (string)$row['category_id'],
            date: $this->formatDate((string)$row['created_at']),
            authorName: (string)($row['author_name'] ?: 'Автор'),
            authorAvatar: '',
            readTime: (int)$row['read_time'],
        );
    }

    private function formatDate(string $date): string
    {
        $time = strtotime($date);

        if ($time === false) {
            return $date;
        }

        return (int)date('j', $time) . ' ' . self::MONTHS[(int)date('n', $time)] . ' ' . date('Y', $time) . ' г.';
    }
}
