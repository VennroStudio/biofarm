<?php

declare(strict_types=1);

namespace App\Http\Unifier\BlogPage;

use App\Components\Seo\JsonLdFactory;
use App\Components\Seo\SeoUrlGenerator;
use App\Http\View\Blog\BlogPostPageView;
use App\Http\View\Blog\BlogPostView;
use App\Http\View\PageMetaView;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class BlogPostPageUnifier
{
    private const array MONTHS = [
        1  => 'января',
        2  => 'февраля',
        3  => 'марта',
        4  => 'апреля',
        5  => 'мая',
        6  => 'июня',
        7  => 'июля',
        8  => 'августа',
        9  => 'сентября',
        10 => 'октября',
        11 => 'ноября',
        12 => 'декабря',
    ];

    public function __construct(
        private Connection $connection,
        private SeoUrlGenerator $urls,
        private JsonLdFactory $jsonLd,
    ) {}

    /**
     * @throws Exception
     */
    public function unify(string $slug): BlogPostPageView
    {
        $post = $this->post($slug);
        $title = $post?->seoTitle ?: ($post !== null ? $post->title . ' — БИОФАРМ' : 'Статья — БИОФАРМ');
        $description = $post?->seoDescription ?: ($post?->excerpt ?? 'Материал блога БИОФАРМ.');
        $canonicalUrl = $this->urls->absolute('/blog/' . trim($slug));

        return new BlogPostPageView(
            meta: new PageMetaView(
                title: $title,
                description: $description,
                canonicalUrl: $canonicalUrl,
                robots: $post !== null ? 'index, follow' : 'noindex, follow',
                ogTitle: $post?->h1 ?: $post?->title ?: $title,
                ogDescription: $description,
                ogImage: $post !== null ? $this->urls->absolute($post->image) : $this->urls->absolute('/assets/images/og/default.jpg'),
                ogImageAlt: $post?->imageAlt ?: $post?->title ?: 'Статья БИОФАРМ',
                ogType: 'article',
                jsonLd: $post !== null ? [
                    $this->jsonLd->breadcrumbs([
                        ['name' => 'Главная', 'url' => '/'],
                        ['name' => 'Блог', 'url' => '/blog'],
                        ['name' => $post->title, 'url' => '/blog/' . $post->slug],
                    ]),
                    $this->jsonLd->blogPosting($post),
                ] : [],
            ),
            post: $post,
            relatedPosts: $post !== null ? $this->relatedPosts($post) : [],
        );
    }

    /**
     * @throws Exception
     */
    private function post(string $slug): ?BlogPostView
    {
        $row = $this->connection->createQueryBuilder()
            ->select(
                'bp.id',
                'bp.slug',
                'bp.title',
                'bp.h1',
                'bp.seo_title',
                'bp.seo_description',
                'bp.excerpt',
                'bp.content',
                'bp.image',
                'bp.image_alt',
                'bp.category_id',
                'COALESCE(bp.published_at, bp.created_at) AS published_at',
                'COALESCE(bc.name, bp.category_id) AS category_name',
                'bc.slug AS category_slug',
                'bp.author_name',
                'bp.read_time',
            )
            ->from('blog_posts', 'bp')
            ->leftJoin('bp', 'blog_categories', 'bc', 'bc.slug = bp.category_id AND bc.deleted_at IS NULL')
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
     * @throws Exception
     */
    private function relatedPosts(BlogPostView $post): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = $this->connection->createQueryBuilder()
            ->select(
                'bp.id',
                'bp.slug',
                'bp.title',
                'bp.h1',
                'bp.seo_title',
                'bp.seo_description',
                'bp.excerpt',
                'bp.content',
                'bp.image',
                'bp.image_alt',
                'bp.category_id',
                'COALESCE(bp.published_at, bp.created_at) AS published_at',
                'COALESCE(bc.name, bp.category_id) AS category_name',
                'bc.slug AS category_slug',
                'bp.author_name',
                'bp.read_time',
            )
            ->from('blog_posts', 'bp')
            ->leftJoin('bp', 'blog_categories', 'bc', 'bc.slug = bp.category_id AND bc.deleted_at IS NULL')
            ->where('bp.slug != :slug')
            ->andWhere('bp.category_id = :category')
            ->andWhere('bp.deleted_at IS NULL')
            ->andWhere('bp.is_published = 1')
            ->setParameter('slug', $post->slug)
            ->setParameter('category', $post->categorySlug ?? $post->category)
            ->orderBy('bp.created_at', 'DESC')
            ->addOrderBy('bp.id', 'DESC')
            ->setMaxResults(3)
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map($this->mapPost(...), $rows);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapPost(array $row): BlogPostView
    {
        return new BlogPostView(
            id: (int)$row['id'],
            slug: (string)$row['slug'],
            title: (string)$row['title'],
            h1: $row['h1'] !== null && trim((string)$row['h1']) !== '' ? (string)$row['h1'] : null,
            seoTitle: $row['seo_title'] !== null && trim((string)$row['seo_title']) !== '' ? (string)$row['seo_title'] : null,
            seoDescription: $row['seo_description'] !== null && trim((string)$row['seo_description']) !== '' ? (string)$row['seo_description'] : null,
            excerpt: (string)$row['excerpt'],
            content: (string)$row['content'],
            image: (string)$row['image'],
            imageAlt: $row['image_alt'] !== null && trim((string)$row['image_alt']) !== '' ? (string)$row['image_alt'] : (string)$row['title'],
            category: (string)$row['category_name'],
            categorySlug: $row['category_slug'] !== null && trim((string)$row['category_slug']) !== '' ? (string)$row['category_slug'] : (string)$row['category_id'],
            date: $this->formatDate((string)$row['published_at']),
            publishedAt: (string)$row['published_at'],
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
