<?php

declare(strict_types=1);

namespace App\Http\Unifier\BlogPage;

use App\Components\Seo\JsonLdFactory;
use App\Components\Seo\SeoUrlGenerator;
use App\Http\View\Blog\BlogPageView;
use App\Http\View\Blog\BlogPostView;
use App\Http\View\PageMetaView;
use App\Modules\Page\Service\PageSeoProvider;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;

final readonly class BlogPageUnifier
{
    private const int POSTS_PER_PAGE = 9;
    private const array CATEGORIES = [
        'Все'      => null,
        'Советы'  => 'tips',
        'Здоровье' => 'health',
        'О нас'   => 'about',
        'Рецепты' => 'recipes',
    ];
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
        private PageSeoProvider $pages,
    ) {}

    /**
     * @throws Exception
     */
    public function unify(?string $selectedCategory = null, ?string $searchQuery = null, int $page = 1): BlogPageView
    {
        $selectedCategory = $this->normalizeCategory($selectedCategory);
        $searchQuery = trim((string)$searchQuery);
        $totalPosts = $this->countPosts($selectedCategory, $searchQuery);
        $totalPages = max(1, (int)ceil($totalPosts / self::POSTS_PER_PAGE));
        $currentPage = min(max(1, $page), $totalPages);
        $posts = $this->posts($selectedCategory, $searchQuery, $currentPage);
        $featuredPost = $currentPage === 1 ? ($posts[0] ?? null) : null;
        $canonicalUrl = $this->urls->absolute('/blog');
        $isFiltered = $selectedCategory !== 'Все' || $searchQuery !== '' || $currentPage > 1;

        return new BlogPageView(
            meta: $this->pages->applySystem('blog', new PageMetaView(
                title: 'Блог — БИОФАРМ',
                description: 'Статьи и новости БИОФАРМ.',
                canonicalUrl: $canonicalUrl,
                robots: $isFiltered ? 'noindex, follow' : 'index, follow',
                ogTitle: 'Блог — БИОФАРМ',
                ogDescription: 'Статьи и новости БИОФАРМ.',
                ogImage: $this->urls->absolute('/assets/images/og/default.jpg'),
                ogImageAlt: 'Блог БИОФАРМ',
                jsonLd: [
                    $this->jsonLd->breadcrumbs([
                        ['name' => 'Главная', 'url' => '/'],
                        ['name' => 'Блог', 'url' => '/blog'],
                    ]),
                ],
            )),
            posts: $posts,
            featuredPost: $featuredPost,
            otherPosts: $currentPage === 1 ? \array_slice($posts, 1) : $posts,
            categories: array_keys(self::CATEGORIES),
            categoryUrls: $this->categoryUrls($searchQuery),
            selectedCategory: $selectedCategory,
            searchQuery: $searchQuery,
            currentPage: $currentPage,
            totalPages: $totalPages,
            totalPosts: $totalPosts,
            paginationItems: $this->paginationItems($currentPage, $totalPages, $selectedCategory, $searchQuery),
            previousPageUrl: $currentPage > 1 ? $this->pageUrl($currentPage - 1, $selectedCategory, $searchQuery) : null,
            nextPageUrl: $currentPage < $totalPages ? $this->pageUrl($currentPage + 1, $selectedCategory, $searchQuery) : null,
        );
    }

    /**
     * @return list<BlogPostView>
     * @throws Exception
     */
    private function posts(string $selectedCategory, string $searchQuery, int $currentPage): array
    {
        $rows = $this->filteredQuery($selectedCategory, $searchQuery)
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
            ->orderBy('bp.created_at', 'DESC')
            ->addOrderBy('bp.id', 'DESC')
            ->setFirstResult(($currentPage - 1) * self::POSTS_PER_PAGE)
            ->setMaxResults(self::POSTS_PER_PAGE)
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map($this->mapPost(...), $rows);
    }

    /**
     * @throws Exception
     */
    private function countPosts(string $selectedCategory, string $searchQuery): int
    {
        return (int)$this->filteredQuery($selectedCategory, $searchQuery)
            ->select('COUNT(bp.id)')
            ->executeQuery()
            ->fetchOne();
    }

    private function filteredQuery(string $selectedCategory, string $searchQuery): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder()
            ->from('blog_posts', 'bp')
            ->leftJoin('bp', 'blog_categories', 'bc', 'bc.slug = bp.category_id AND bc.deleted_at IS NULL')
            ->where('bp.deleted_at IS NULL')
            ->andWhere('bp.is_published = 1');

        if ($selectedCategory !== 'Все') {
            $query
                ->andWhere('bp.category_id = :category')
                ->setParameter('category', self::CATEGORIES[$selectedCategory]);
        }

        if ($searchQuery !== '') {
            $query
                ->andWhere('(bp.title LIKE :search OR bp.excerpt LIKE :search)')
                ->setParameter('search', '%' . $searchQuery . '%');
        }

        return $query;
    }

    private function normalizeCategory(?string $category): string
    {
        $category = trim((string)$category);

        return \array_key_exists($category, self::CATEGORIES) ? $category : 'Все';
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

    /**
     * @return array<string, string>
     */
    private function categoryUrls(string $searchQuery): array
    {
        $urls = [];

        foreach (array_keys(self::CATEGORIES) as $category) {
            $urls[$category] = $this->pageUrl(1, $category, $searchQuery);
        }

        return $urls;
    }

    /**
     * @return list<array{type: string, page?: int, url?: string, current?: bool}>
     */
    private function paginationItems(int $currentPage, int $totalPages, string $selectedCategory, string $searchQuery): array
    {
        if ($totalPages <= 1) {
            return [];
        }

        $visiblePages = [];

        for ($page = 1; $page <= $totalPages; ++$page) {
            if ($page === 1 || $page === $totalPages || abs($page - $currentPage) <= 2) {
                $visiblePages[] = $page;
            }
        }

        $items = [];
        $previousPage = null;

        foreach ($visiblePages as $page) {
            if ($previousPage !== null && $previousPage !== $page - 1) {
                $items[] = ['type' => 'ellipsis'];
            }

            $items[] = [
                'type'    => 'page',
                'page'    => $page,
                'url'     => $this->pageUrl($page, $selectedCategory, $searchQuery),
                'current' => $page === $currentPage,
            ];

            $previousPage = $page;
        }

        return $items;
    }

    private function pageUrl(int $page, string $selectedCategory, string $searchQuery): string
    {
        $query = [];

        if ($selectedCategory !== 'Все') {
            $query['category'] = $selectedCategory;
        }

        if ($searchQuery !== '') {
            $query['q'] = $searchQuery;
        }

        if ($page > 1) {
            $query['page'] = $page;
        }

        return '/blog' . ($query === [] ? '' : '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986));
    }
}
