<?php

declare(strict_types=1);

namespace App\Http\Unifier\BlogPage;

use App\Http\View\Blog\BlogPageView;
use App\Http\View\Blog\BlogPostView;
use App\Http\View\PageMetaView;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;

final readonly class BlogPageUnifier
{
    private const int POSTS_PER_PAGE = 9;
    private const array CATEGORIES = ['Все', 'Советы', 'Здоровье', 'О нас', 'Рецепты'];
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

        return new BlogPageView(
            meta: new PageMetaView(
                title: 'Блог — БИОФАРМ',
                description: 'Статьи и новости БИОФАРМ.',
            ),
            posts: $posts,
            featuredPost: $featuredPost,
            otherPosts: $currentPage === 1 ? \array_slice($posts, 1) : $posts,
            categories: self::CATEGORIES,
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
                'bp.excerpt',
                'bp.content',
                'bp.image',
                'bp.category_id',
                'bp.created_at',
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
            ->where('bp.deleted_at IS NULL')
            ->andWhere('bp.is_published = 1');

        if ($selectedCategory !== 'Все') {
            $query
                ->andWhere('bp.category_id = :category')
                ->setParameter('category', $selectedCategory);
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

        return \in_array($category, self::CATEGORIES, true) ? $category : 'Все';
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

    /**
     * @return array<string, string>
     */
    private function categoryUrls(string $searchQuery): array
    {
        $urls = [];

        foreach (self::CATEGORIES as $category) {
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
