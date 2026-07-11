<?php

declare(strict_types=1);

namespace App\Http\Unifier\Home;

use App\Http\Unifier\Product\ProductCatalogDataProvider;
use App\Http\View\Blog\BlogPostView;
use App\Http\View\Home\HomeCategoryView;
use App\Http\View\Home\HomePageView;
use App\Http\View\Home\HomeReviewView;
use App\Http\View\PageMetaView;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class HomePageUnifier
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
        private ProductCatalogDataProvider $catalogData,
        private Connection $connection,
    ) {}

    public function unify(?string $selectedCategory = null): HomePageView
    {
        $products = $this->catalogData->products($selectedCategory, 6);
        $categories = $this->catalogData->categories();

        return new HomePageView(
            meta: new PageMetaView(
                title: 'БИОФАРМ — натуральные продукты',
                description: 'Экологически чистые продукты БИОФАРМ напрямую из собственных лабораторий.',
            ),
            products: $products,
            selectedCategory: $selectedCategory,
            featuredProduct: $products[0] ?? null,
            categories: $categories,
            categoriesTotal: array_sum(array_map(
                static fn (HomeCategoryView $category): int => $category->productsCount,
                $categories,
            )),
            blogPosts: $this->blogPosts(),
            reviews: $this->reviews(),
        );
    }

    /**
     * @return list<BlogPostView>
     * @throws Exception
     */
    private function blogPosts(): array
    {
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
            ->where('bp.deleted_at IS NULL')
            ->andWhere('bp.is_published = 1')
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

    /**
     * @return list<HomeReviewView>
     * @throws Exception
     */
    private function reviews(): array
    {
        $rows = $this->connection->createQueryBuilder()
            ->select(
                'r.id',
                'r.user_name',
                'r.rating',
                'r.text',
                'r.images',
                'r.created_at',
                'COALESCE(p.name, :fallback_product) AS product_name',
            )
            ->from('reviews', 'r')
            ->leftJoin('r', 'products', 'p', 'p.id = r.product_id AND p.deleted_at IS NULL')
            ->where('r.deleted_at IS NULL')
            ->andWhere('r.is_approved = 1')
            ->setParameter('fallback_product', 'Товар')
            ->orderBy('r.created_at', 'DESC')
            ->setMaxResults(5)
            ->executeQuery()
            ->fetchAllAssociative();

        $reviews = array_map($this->mapReview(...), $rows);

        if ($reviews !== [] && \count($reviews) < 3) {
            while (\count($reviews) < 3) {
                $reviews = [...$reviews, ...$reviews];
            }

            $reviews = \array_slice($reviews, 0, 3);
        }

        return $reviews;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapReview(array $row): HomeReviewView
    {
        $name = (string)$row['user_name'];

        return new HomeReviewView(
            id: (string)$row['id'],
            name: $name,
            avatar: 'https://ui-avatars.com/api/?name=' . rawurlencode($name) . '&background=random',
            rating: (int)$row['rating'],
            text: (string)$row['text'],
            date: $this->formatDate((string)$row['created_at']),
            product: (string)$row['product_name'],
            images: $this->jsonList($row['images']),
        );
    }

    /**
     * @return list<string>
     */
    private function jsonList(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        if (\is_array($value)) {
            return array_values(array_filter($value, static fn (mixed $item): bool => \is_string($item) && $item !== ''));
        }

        if (!\is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        if (!\is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, static fn (mixed $item): bool => \is_string($item) && $item !== ''));
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
