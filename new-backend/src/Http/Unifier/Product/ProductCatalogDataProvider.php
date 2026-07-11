<?php

declare(strict_types=1);

namespace App\Http\Unifier\Product;

use App\Http\View\Home\HomeCategoryView;
use App\Http\View\Product\ProductCardView;
use App\Http\View\Product\ProductPageProductView;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;

final readonly class ProductCatalogDataProvider
{
    private const array FALLBACK_CATEGORY_NAMES = [
        '1' => 'Готовая оздоровительная продукция',
        '2' => 'Сырье для изготовления оздоровительной продукции',
    ];

    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * @return list<ProductCardView>
     * @throws Exception
     */
    public function products(
        ?string $selectedCategory = null,
        ?int $limit = null,
        ?string $search = null,
        string $sort = 'default',
        int $offset = 0,
    ): array {
        $category = $this->normalizeCategory($selectedCategory);
        $query = $this->normalizeSearch($search);

        $qb = $this->connection->createQueryBuilder()
            ->select(
                'p.id',
                'p.slug',
                'p.name',
                'p.category_id',
                'p.price',
                'p.old_price',
                'p.image',
                'p.badge',
                'p.weight',
                'p.description',
                'p.short_description',
                'c.name AS category_name',
            )
            ->from('products', 'p')
            ->leftJoin('p', 'categories', 'c', 'c.id = CAST(p.category_id AS UNSIGNED) AND c.deleted_at IS NULL')
            ->where('p.deleted_at IS NULL')
            ->andWhere('p.is_active = 1');

        $this->applyFilters($qb, $category, $query);
        $this->applySort($qb, $sort);

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        if ($offset > 0) {
            $qb->setFirstResult($offset);
        }

        /** @var list<array{id: int|string, slug: string, name: string, category_id: string, price: int|string, old_price: int|string|null, image: string, badge: string|null, weight: string, description: string, short_description: string|null, category_name: string|null}> $rows */
        $rows = $qb->executeQuery()->fetchAllAssociative();

        return array_map($this->mapProduct(...), $rows);
    }

    /**
     * @throws Exception
     */
    public function countProducts(?string $selectedCategory = null, ?string $search = null): int
    {
        $category = $this->normalizeCategory($selectedCategory);
        $query = $this->normalizeSearch($search);

        $qb = $this->connection->createQueryBuilder()
            ->select('COUNT(p.id)')
            ->from('products', 'p')
            ->where('p.deleted_at IS NULL')
            ->andWhere('p.is_active = 1');

        $this->applyFilters($qb, $category, $query);

        return (int)$qb->executeQuery()->fetchOne();
    }

    /**
     * @throws Exception
     */
    public function productBySlug(string $slug): ?ProductCardView
    {
        $slug = trim($slug);

        if ($slug === '') {
            return null;
        }

        /** @var array{id: int|string, slug: string, name: string, category_id: string, price: int|string, old_price: int|string|null, image: string, badge: string|null, weight: string, description: string, short_description: string|null, category_name: string|null}|false $row */
        $row = $this->connection->createQueryBuilder()
            ->select(
                'p.id',
                'p.slug',
                'p.name',
                'p.category_id',
                'p.price',
                'p.old_price',
                'p.image',
                'p.badge',
                'p.weight',
                'p.description',
                'p.short_description',
                'c.name AS category_name',
            )
            ->from('products', 'p')
            ->leftJoin('p', 'categories', 'c', 'c.id = CAST(p.category_id AS UNSIGNED) AND c.deleted_at IS NULL')
            ->where('p.deleted_at IS NULL')
            ->andWhere('p.is_active = 1')
            ->andWhere('p.slug = :slug')
            ->setParameter('slug', $slug)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        return $row === false ? null : $this->mapProduct($row);
    }

    /**
     * @throws Exception
     */
    public function pageProductBySlug(string $slug): ?ProductPageProductView
    {
        $slug = trim($slug);

        if ($slug === '') {
            return null;
        }

        /** @var array{id: int|string, slug: string, name: string, category_id: string, price: int|string, old_price: int|string|null, image: string, images: list<string>|string|null, badge: string|null, weight: string, description: string, short_description: string|null, ingredients: string|null, features: list<string>|string|null, wb_link: string|null, ozon_link: string|null, category_name: string|null}|false $row */
        $row = $this->connection->createQueryBuilder()
            ->select(
                'p.id',
                'p.slug',
                'p.name',
                'p.category_id',
                'p.price',
                'p.old_price',
                'p.image',
                'p.images',
                'p.badge',
                'p.weight',
                'p.description',
                'p.short_description',
                'p.ingredients',
                'p.features',
                'p.wb_link',
                'p.ozon_link',
                'c.name AS category_name',
            )
            ->from('products', 'p')
            ->leftJoin('p', 'categories', 'c', 'c.id = CAST(p.category_id AS UNSIGNED) AND c.deleted_at IS NULL')
            ->where('p.deleted_at IS NULL')
            ->andWhere('p.is_active = 1')
            ->andWhere('p.slug = :slug')
            ->setParameter('slug', $slug)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        return $row === false ? null : $this->mapPageProduct($row);
    }

    /**
     * @return list<ProductCardView>
     * @throws Exception
     */
    public function relatedProducts(string $categoryId, int $excludeProductId, int $limit = 4): array
    {
        /** @var list<array{id: int|string, slug: string, name: string, category_id: string, price: int|string, old_price: int|string|null, image: string, badge: string|null, weight: string, description: string, short_description: string|null, category_name: string|null}> $rows */
        $rows = $this->connection->createQueryBuilder()
            ->select(
                'p.id',
                'p.slug',
                'p.name',
                'p.category_id',
                'p.price',
                'p.old_price',
                'p.image',
                'p.badge',
                'p.weight',
                'p.description',
                'p.short_description',
                'c.name AS category_name',
            )
            ->from('products', 'p')
            ->leftJoin('p', 'categories', 'c', 'c.id = CAST(p.category_id AS UNSIGNED) AND c.deleted_at IS NULL')
            ->where('p.deleted_at IS NULL')
            ->andWhere('p.is_active = 1')
            ->andWhere('p.category_id = :category')
            ->andWhere('p.id != :excludeProductId')
            ->setParameter('category', $categoryId)
            ->setParameter('excludeProductId', $excludeProductId)
            ->orderBy('p.created_at', 'DESC')
            ->addOrderBy('p.id', 'ASC')
            ->setMaxResults($limit)
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map($this->mapProduct(...), $rows);
    }

    /**
     * @return list<HomeCategoryView>
     * @throws Exception
     */
    public function categories(): array
    {
        $counts = $this->categoryCounts();
        $categoryRows = $this->connection->createQueryBuilder()
            ->select('CAST(c.id AS CHAR) AS id', 'c.name')
            ->from('categories', 'c')
            ->where('c.deleted_at IS NULL')
            ->orderBy('c.id', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        $categories = [];
        if ($categoryRows !== []) {
            foreach ($categoryRows as $row) {
                $id = (string)$row['id'];
                $categories[$id] = new HomeCategoryView(
                    id: $id,
                    name: (string)$row['name'],
                    productsCount: $counts[$id] ?? 0,
                );
            }
        } else {
            foreach (self::FALLBACK_CATEGORY_NAMES as $id => $name) {
                $id = (string)$id;
                $categories[$id] = new HomeCategoryView(
                    id: $id,
                    name: $name,
                    productsCount: $counts[$id] ?? 0,
                );
            }
        }

        foreach ($counts as $id => $count) {
            $id = (string)$id;
            if (!isset($categories[$id])) {
                $categories[$id] = new HomeCategoryView(
                    id: $id,
                    name: self::FALLBACK_CATEGORY_NAMES[$id] ?? $id,
                    productsCount: $count,
                );
            }
        }

        return array_values($categories);
    }

    /**
     * @param array{id: int|string, slug: string, name: string, category_id: string, price: int|string, old_price: int|string|null, image: string, badge: string|null, weight: string, description: string, short_description: string|null, category_name: string|null} $row
     */
    private function mapProduct(array $row): ProductCardView
    {
        $description = $this->plainText((string)$row['description']);
        $shortDescription = $row['short_description'] !== null
            ? $this->plainText((string)$row['short_description'])
            : null;
        $categoryId = (string)$row['category_id'];

        return new ProductCardView(
            id: (int)$row['id'],
            slug: (string)$row['slug'],
            title: (string)$row['name'],
            price: (float)$row['price'],
            oldPrice: $row['old_price'] !== null ? (float)$row['old_price'] : null,
            description: $description,
            shortDescription: $shortDescription,
            categoryId: $categoryId,
            category: $this->categoryName($categoryId, $row['category_name']),
            brand: 'БИОФАРМ',
            stock: 0,
            image: (string)$row['image'],
            badge: $row['badge'] !== null && trim((string)$row['badge']) !== '' ? (string)$row['badge'] : null,
            weight: (string)$row['weight'],
        );
    }

    /**
     * @param array{id: int|string, slug: string, name: string, category_id: string, price: int|string, old_price: int|string|null, image: string, images: list<string>|string|null, badge: string|null, weight: string, description: string, short_description: string|null, ingredients: string|null, features: list<string>|string|null, wb_link: string|null, ozon_link: string|null, category_name: string|null} $row
     */
    private function mapPageProduct(array $row): ProductPageProductView
    {
        $categoryId = (string)$row['category_id'];
        $image = (string)$row['image'];
        $descriptionHtml = trim((string)$row['description']);
        $description = $this->plainText($descriptionHtml);
        $shortDescription = $row['short_description'] !== null
            ? $this->plainText((string)$row['short_description'])
            : null;

        return new ProductPageProductView(
            id: (int)$row['id'],
            slug: (string)$row['slug'],
            title: (string)$row['name'],
            price: (float)$row['price'],
            oldPrice: $row['old_price'] !== null ? (float)$row['old_price'] : null,
            description: $description,
            descriptionHtml: $descriptionHtml,
            shortDescription: $shortDescription,
            categoryId: $categoryId,
            category: $this->categoryName($categoryId, $row['category_name']),
            image: $image,
            images: $this->galleryImages($image, $this->jsonList($row['images'])),
            badge: $row['badge'] !== null && trim((string)$row['badge']) !== '' ? (string)$row['badge'] : null,
            weight: (string)$row['weight'],
            ingredients: $row['ingredients'] !== null && trim((string)$row['ingredients']) !== '' ? (string)$row['ingredients'] : null,
            features: $this->jsonList($row['features']),
            wbLink: $row['wb_link'] !== null && trim((string)$row['wb_link']) !== '' ? (string)$row['wb_link'] : null,
            ozonLink: $row['ozon_link'] !== null && trim((string)$row['ozon_link']) !== '' ? (string)$row['ozon_link'] : null,
        );
    }

    /**
     * @return array<string, int>
     * @throws Exception
     */
    private function categoryCounts(): array
    {
        /** @var list<array{category_id: string, products_count: int|string}> $rows */
        $rows = $this->connection->createQueryBuilder()
            ->select('p.category_id', 'COUNT(p.id) AS products_count')
            ->from('products', 'p')
            ->where('p.deleted_at IS NULL')
            ->andWhere('p.is_active = 1')
            ->groupBy('p.category_id')
            ->orderBy('p.category_id', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(string)$row['category_id']] = (int)$row['products_count'];
        }

        return $counts;
    }

    private function normalizeCategory(?string $selectedCategory): ?string
    {
        $category = trim((string)$selectedCategory);

        return $category === '' || $category === 'all' ? null : $category;
    }

    private function normalizeSearch(?string $search): ?string
    {
        $query = trim((string)$search);

        return $query === '' ? null : mb_substr($query, 0, 80);
    }

    private function applyFilters(QueryBuilder $qb, ?string $category, ?string $search): void
    {
        if ($category !== null) {
            $qb->andWhere('p.category_id = :category')
                ->setParameter('category', $category);
        }

        if ($search !== null) {
            $like = '%' . $this->escapeLike($search) . '%';
            $qb->andWhere(
                '(p.name LIKE :search ESCAPE \'!\' OR p.description LIKE :search ESCAPE \'!\' OR p.short_description LIKE :search ESCAPE \'!\')',
            )->setParameter('search', $like);
        }
    }

    private function applySort(QueryBuilder $qb, string $sort): void
    {
        match ($sort) {
            'price-asc'  => $qb->orderBy('p.price', 'ASC')->addOrderBy('p.id', 'ASC'),
            'price-desc' => $qb->orderBy('p.price', 'DESC')->addOrderBy('p.id', 'ASC'),
            'name'       => $qb->orderBy('p.name', 'ASC')->addOrderBy('p.id', 'ASC'),
            default      => $qb->orderBy('p.created_at', 'DESC')->addOrderBy('p.id', 'ASC'),
        };
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }

    private function categoryName(string $categoryId, ?string $name): string
    {
        $label = trim((string)$name);
        if ($label !== '') {
            return $label;
        }

        return self::FALLBACK_CATEGORY_NAMES[$categoryId] ?? $categoryId;
    }

    private function plainText(string $html): string
    {
        return trim((string)preg_replace('/\s+/u', ' ', strip_tags($html)));
    }

    /**
     * @return list<string>
     */
    private function jsonList(array|string|null $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (\is_array($value)) {
            return array_values(array_filter($value, static fn (mixed $item): bool => \is_string($item) && trim($item) !== ''));
        }

        $decoded = json_decode($value, true);

        if (!\is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, static fn (mixed $item): bool => \is_string($item) && trim($item) !== ''));
    }

    /**
     * @param list<string> $images
     * @return list<string>
     */
    private function galleryImages(string $mainImage, array $images): array
    {
        return array_values(array_unique(array_filter([$mainImage, ...$images], static fn (string $image): bool => trim($image) !== '')));
    }
}
