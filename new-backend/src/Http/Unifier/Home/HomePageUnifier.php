<?php

declare(strict_types=1);

namespace App\Http\Unifier\Home;

use App\Components\Seo\JsonLdFactory;
use App\Components\Seo\SeoUrlGenerator;
use App\Http\Unifier\Product\ProductCatalogDataProvider;
use App\Http\View\Blog\BlogPostView;
use App\Http\View\Home\HomeCategoryView;
use App\Http\View\Home\HomePageView;
use App\Http\View\Home\HomeReviewView;
use App\Http\View\PageMetaView;
use App\Modules\Page\Service\PageSeoProvider;
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
        private SeoUrlGenerator $urls,
        private JsonLdFactory $jsonLd,
        private PageSeoProvider $pages,
    ) {}

    public function unify(?string $selectedCategory = null): HomePageView
    {
        $products = $this->catalogData->products($selectedCategory, 6);
        $categories = $this->catalogData->categories();

        return new HomePageView(
            meta: $this->pages->applySystem('home', new PageMetaView(
                title: 'БИОФАРМ — натуральные продукты',
                description: 'Экологически чистые продукты БИОФАРМ напрямую из собственных лабораторий.',
                canonicalUrl: $this->urls->absolute('/'),
                ogTitle: 'БИОФАРМ — натуральные продукты',
                ogDescription: 'Экологически чистые продукты БИОФАРМ напрямую из собственных лабораторий.',
                ogImage: $this->urls->absolute('/assets/images/og/default.jpg'),
                ogImageAlt: 'БИОФАРМ',
                jsonLd: [
                    $this->jsonLd->organization(),
                    $this->jsonLd->website(),
                ],
            )),
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
            initials: $this->reviewInitials($name),
            rating: (int)$row['rating'],
            text: (string)$row['text'],
            date: $this->formatDate((string)$row['created_at']),
            product: (string)$row['product_name'],
            images: $this->jsonList($row['images']),
        );
    }

    private function reviewInitials(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        $letters = [];

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            $letters[] = mb_substr($part, 0, 1);
            if (\count($letters) === 2) {
                break;
            }
        }

        return mb_strtoupper(implode('', $letters) ?: mb_substr($name, 0, 1));
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
