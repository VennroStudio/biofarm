<?php

declare(strict_types=1);

namespace App\Http\Unifier\Product;

use App\Http\View\Catalog\CatalogFacetView;
use App\Http\View\Home\HomeCategoryView;
use App\Http\View\Product\ProductCardView;
use App\Http\View\Product\ProductImageView;
use App\Http\View\Product\ProductPageProductView;
use App\Http\View\Product\ProductVariantView;
use Doctrine\DBAL\ArrayParameterType;
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
        ?string $componentSlug = null,
        ?string $purposeSlug = null,
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
                'COALESCE(pi.path, p.image) AS image',
                'COALESCE(pi.alt, p.image_alt, p.name) AS image_alt',
                'p.badge',
                'p.weight',
                'p.description',
                'p.short_description',
                'c.name AS category_name',
                'c.slug AS category_slug',
            )
            ->from('products', 'p')
            ->leftJoin('p', 'categories', 'c', 'c.id = CAST(p.category_id AS UNSIGNED) AND c.deleted_at IS NULL')
            ->leftJoin('p', 'product_images', 'pi', 'pi.product_id = p.id AND pi.is_main = 1')
            ->where('p.deleted_at IS NULL')
            ->andWhere('p.is_active = 1');

        $this->applyFilters($qb, $category, $query, $componentSlug, $purposeSlug);
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
    public function countProducts(
        ?string $selectedCategory = null,
        ?string $search = null,
        ?string $componentSlug = null,
        ?string $purposeSlug = null,
    ): int
    {
        $category = $this->normalizeCategory($selectedCategory);
        $query = $this->normalizeSearch($search);

        $qb = $this->connection->createQueryBuilder()
            ->select('COUNT(p.id)')
            ->from('products', 'p')
            ->where('p.deleted_at IS NULL')
            ->andWhere('p.is_active = 1');

        $this->applyFilters($qb, $category, $query, $componentSlug, $purposeSlug);

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
                'COALESCE(pi.path, p.image) AS image',
                'COALESCE(pi.alt, p.image_alt, p.name) AS image_alt',
                'p.badge',
                'p.weight',
                'p.description',
                'p.short_description',
                'c.name AS category_name',
                'c.slug AS category_slug',
            )
            ->from('products', 'p')
            ->leftJoin('p', 'categories', 'c', 'c.id = CAST(p.category_id AS UNSIGNED) AND c.deleted_at IS NULL')
            ->leftJoin('p', 'product_images', 'pi', 'pi.product_id = p.id AND pi.is_main = 1')
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
                'p.h1',
                'p.seo_title',
                'p.seo_description',
                'p.category_id',
                'p.price',
                'p.old_price',
                'COALESCE(pi.path, p.image) AS image',
                'COALESCE(pi.alt, p.image_alt, p.name) AS image_alt',
                'p.images',
                'p.badge',
                'p.weight',
                'p.sku',
                'p.gtin',
                'p.availability',
                'p.description',
                'p.short_description',
                'p.ingredients',
                'p.features',
                'p.wb_link',
                'p.ozon_link',
                'c.name AS category_name',
                'c.slug AS category_slug',
            )
            ->from('products', 'p')
            ->leftJoin('p', 'categories', 'c', 'c.id = CAST(p.category_id AS UNSIGNED) AND c.deleted_at IS NULL')
            ->leftJoin('p', 'product_images', 'pi', 'pi.product_id = p.id AND pi.is_main = 1')
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
                'COALESCE(pi.path, p.image) AS image',
                'COALESCE(pi.alt, p.image_alt, p.name) AS image_alt',
                'p.badge',
                'p.weight',
                'p.description',
                'p.short_description',
                'c.name AS category_name',
                'c.slug AS category_slug',
            )
            ->from('products', 'p')
            ->leftJoin('p', 'categories', 'c', 'c.id = CAST(p.category_id AS UNSIGNED) AND c.deleted_at IS NULL')
            ->leftJoin('p', 'product_images', 'pi', 'pi.product_id = p.id AND pi.is_main = 1')
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
            ->select('CAST(c.id AS CHAR) AS id', 'c.slug', 'c.name', 'p.slug AS parent_slug')
            ->from('categories', 'c')
            ->leftJoin('c', 'categories', 'p', 'p.id = c.parent_id AND p.deleted_at IS NULL')
            ->where('c.deleted_at IS NULL')
            ->orderBy('COALESCE(c.parent_id, c.id)', 'ASC')
            ->addOrderBy('c.parent_id IS NOT NULL', 'ASC')
            ->addOrderBy('c.sort_order', 'ASC')
            ->addOrderBy('c.id', 'ASC')
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
                    slug: (string)$row['slug'],
                    parentSlug: $row['parent_slug'] !== null && trim((string)$row['parent_slug']) !== '' ? (string)$row['parent_slug'] : null,
                );
            }
        } else {
            foreach (self::FALLBACK_CATEGORY_NAMES as $id => $name) {
                $id = (string)$id;
                $categories[$id] = new HomeCategoryView(
                    id: $id,
                    name: $name,
                    productsCount: $counts[$id] ?? 0,
                    slug: null,
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
                    slug: null,
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
            imageAlt: $row['image_alt'] !== null && trim((string)$row['image_alt']) !== '' ? (string)$row['image_alt'] : (string)$row['name'],
            categorySlug: $row['category_slug'] !== null && trim((string)$row['category_slug']) !== '' ? (string)$row['category_slug'] : null,
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
        $rating = $this->rating((int)$row['id']);
        $imageItems = $this->productImageItems(
            productId: (int)$row['id'],
            mainImage: $image,
            mainAlt: $row['image_alt'] !== null && trim((string)$row['image_alt']) !== '' ? (string)$row['image_alt'] : (string)$row['name'],
            title: (string)$row['name'],
            fallbackImages: $this->jsonList($row['images']),
        );

        return new ProductPageProductView(
            id: (int)$row['id'],
            slug: (string)$row['slug'],
            title: (string)$row['name'],
            h1: $row['h1'] !== null && trim((string)$row['h1']) !== '' ? (string)$row['h1'] : null,
            seoTitle: $row['seo_title'] !== null && trim((string)$row['seo_title']) !== '' ? (string)$row['seo_title'] : null,
            seoDescription: $row['seo_description'] !== null && trim((string)$row['seo_description']) !== '' ? (string)$row['seo_description'] : null,
            price: (float)$row['price'],
            oldPrice: $row['old_price'] !== null ? (float)$row['old_price'] : null,
            description: $description,
            descriptionHtml: $descriptionHtml,
            shortDescription: $shortDescription,
            categoryId: $categoryId,
            category: $this->categoryName($categoryId, $row['category_name']),
            categorySlug: $row['category_slug'] !== null && trim((string)$row['category_slug']) !== '' ? (string)$row['category_slug'] : null,
            image: $image,
            imageAlt: $row['image_alt'] !== null && trim((string)$row['image_alt']) !== '' ? (string)$row['image_alt'] : (string)$row['name'],
            images: array_map(static fn (ProductImageView $image): string => $image->path, $imageItems),
            imageItems: $imageItems,
            badge: $row['badge'] !== null && trim((string)$row['badge']) !== '' ? (string)$row['badge'] : null,
            weight: (string)$row['weight'],
            sku: $row['sku'] !== null && trim((string)$row['sku']) !== '' ? (string)$row['sku'] : null,
            gtin: $row['gtin'] !== null && trim((string)$row['gtin']) !== '' ? (string)$row['gtin'] : null,
            availability: $row['availability'] !== null && trim((string)$row['availability']) !== '' ? (string)$row['availability'] : 'in_stock',
            ingredients: $row['ingredients'] !== null && trim((string)$row['ingredients']) !== '' ? (string)$row['ingredients'] : null,
            features: $this->jsonList($row['features']),
            wbLink: $row['wb_link'] !== null && trim((string)$row['wb_link']) !== '' ? (string)$row['wb_link'] : null,
            ozonLink: $row['ozon_link'] !== null && trim((string)$row['ozon_link']) !== '' ? (string)$row['ozon_link'] : null,
            variants: $this->productVariants((int)$row['id']),
            ratingRate: $rating['rate'],
            ratingCount: $rating['count'],
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

        $parents = $this->categoryParentMap();
        $counts = [];
        foreach ($rows as $row) {
            $id = (string)$row['category_id'];
            $count = (int)$row['products_count'];
            $counts[$id] = ($counts[$id] ?? 0) + $count;

            $parentId = $parents[$id] ?? null;
            while ($parentId !== null && $parentId !== '' && $parentId !== $id) {
                $counts[$parentId] = ($counts[$parentId] ?? 0) + $count;
                $id = $parentId;
                $parentId = $parents[$id] ?? null;
            }
        }

        return $counts;
    }

    /**
     * @throws Exception
     */
    public function categoryId(?string $selectedCategory): ?string
    {
        return $this->normalizeCategory($selectedCategory);
    }

    /**
     * @return list<CatalogFacetView>
     * @throws Exception
     */
    public function componentFilters(?string $selectedCategory): array
    {
        return $this->attributeFilters($selectedCategory, 'sostav');
    }

    /**
     * @return list<CatalogFacetView>
     * @throws Exception
     */
    public function purposeFilters(?string $selectedCategory): array
    {
        return $this->attributeFilters($selectedCategory, 'dlya');
    }

    /**
     * @return array{
     *     id: string,
     *     slug: string,
     *     parent_slug: string|null,
     *     name: string,
     *     h1: string|null,
     *     seo_title: string|null,
     *     seo_description: string|null,
     *     intro_text: string|null,
     *     bottom_text: string|null,
     *     is_indexable: bool
     * }|null
     * @throws Exception
     */
    public function categoryContext(?string $categoryId): ?array
    {
        $categoryId = trim((string)$categoryId);
        if ($categoryId === '') {
            return null;
        }

        /** @var array{id: int|string, slug: string, parent_slug: string|null, name: string, h1: string|null, seo_title: string|null, seo_description: string|null, intro_text: string|null, bottom_text: string|null, is_indexable: int|string|bool}|false $row */
        $row = $this->connection->createQueryBuilder()
            ->select(
                'CAST(c.id AS CHAR) AS id',
                'c.slug',
                'p.slug AS parent_slug',
                'c.name',
                'c.h1',
                'c.seo_title',
                'c.seo_description',
                'c.intro_text',
                'c.bottom_text',
                'c.is_indexable',
            )
            ->from('categories', 'c')
            ->leftJoin('c', 'categories', 'p', 'p.id = c.parent_id AND p.deleted_at IS NULL')
            ->where('c.id = :id')
            ->andWhere('c.deleted_at IS NULL')
            ->setParameter('id', $categoryId)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if ($row === false) {
            return null;
        }

        return [
            'id'              => (string)$row['id'],
            'slug'            => (string)$row['slug'],
            'parent_slug'     => $row['parent_slug'] !== null && trim((string)$row['parent_slug']) !== '' ? (string)$row['parent_slug'] : null,
            'name'            => (string)$row['name'],
            'h1'              => $this->nullableText($row['h1']),
            'seo_title'       => $this->nullableText($row['seo_title']),
            'seo_description' => $this->nullableText($row['seo_description']),
            'intro_text'      => $this->nullableText($row['intro_text']),
            'bottom_text'     => $this->nullableText($row['bottom_text']),
            'is_indexable'    => (bool)(int)$row['is_indexable'],
        ];
    }

    /**
     * @return array{
     *     slug: string,
     *     name: string,
     *     h1: string|null,
     *     short_description: string|null,
     *     seo_title: string|null,
     *     seo_description: string|null,
     *     intro_text: string|null,
     *     bottom_text: string|null,
     *     is_indexable: bool
     * }|null
     * @throws Exception
     */
    public function componentContext(?string $componentSlug): ?array
    {
        $row = $this->attributeValueContext('sostav', $componentSlug);
        if ($row === null) {
            return null;
        }

        return [
            'slug'              => (string)$row['slug'],
            'name'              => (string)$row['name'],
            'h1'                => $row['h1'],
            'short_description' => $row['short_description'],
            'seo_title'         => $row['seo_title'],
            'seo_description'   => $row['seo_description'],
            'intro_text'        => $row['intro_text'],
            'bottom_text'       => $row['bottom_text'],
            'is_indexable'      => $row['is_indexable'],
        ];
    }

    /**
     * @return array{
     *     slug: string,
     *     name: string,
     *     h1: string|null,
     *     seo_title: string|null,
     *     seo_description: string|null,
     *     intro_text: string|null,
     *     bottom_text: string|null,
     *     is_indexable: bool
     * }|null
     * @throws Exception
     */
    public function purposeContext(?string $purposeSlug): ?array
    {
        $row = $this->attributeValueContext('dlya', $purposeSlug);
        if ($row === null) {
            return null;
        }

        return [
            'slug'            => (string)$row['slug'],
            'name'            => (string)$row['name'],
            'h1'              => $row['h1'],
            'seo_title'       => $row['seo_title'],
            'seo_description' => $row['seo_description'],
            'intro_text'      => $row['intro_text'],
            'bottom_text'     => $row['bottom_text'],
            'is_indexable'    => $row['is_indexable'],
        ];
    }

    /**
     * @return list<CatalogFacetView>
     * @throws Exception
     */
    private function attributeFilters(?string $selectedCategory, string $attributeSlug): array
    {
        $category = $this->normalizeCategory($selectedCategory);
        $qb = $this->connection->createQueryBuilder()
            ->select('av.slug', 'av.name', 'COUNT(DISTINCT p.id) AS products_count')
            ->from('attribute_values', 'av')
            ->innerJoin('av', 'attributes', 'a', 'a.id = av.attribute_id AND a.slug = :attributeSlug AND a.deleted_at IS NULL AND a.is_filterable = 1')
            ->innerJoin('av', 'product_attribute_values', 'pav', 'pav.attribute_value_id = av.id')
            ->innerJoin('pav', 'products', 'p', 'p.id = pav.product_id AND p.deleted_at IS NULL AND p.is_active = 1')
            ->where('av.deleted_at IS NULL')
            ->groupBy('av.id', 'av.slug', 'av.name', 'av.sort_order')
            ->orderBy('av.sort_order', 'ASC')
            ->addOrderBy('av.name', 'ASC')
            ->setParameter('attributeSlug', $attributeSlug);

        if ($category !== null) {
            $categoryIds = $this->categoryFilterIds($category);
            if ($categoryIds === []) {
                $qb->andWhere('1 = 0');
            } else {
                $qb->andWhere('p.category_id IN (:categoryIds)')
                    ->setParameter('categoryIds', $categoryIds, ArrayParameterType::STRING);
            }
        }

        /** @var list<array{slug: string, name: string, products_count: int|string}> $rows */
        $rows = $qb->executeQuery()->fetchAllAssociative();

        return array_map(
            static fn (array $row): CatalogFacetView => new CatalogFacetView(
                slug: (string)$row['slug'],
                name: (string)$row['name'],
                productsCount: (int)$row['products_count'],
            ),
            $rows,
        );
    }

    /**
     * @return array{
     *     slug: string,
     *     name: string,
     *     h1: string|null,
     *     seo_title: string|null,
     *     seo_description: string|null,
     *     intro_text: string|null,
     *     bottom_text: string|null,
     *     short_description: string|null,
     *     is_indexable: bool
     * }|null
     * @throws Exception
     */
    private function attributeValueContext(string $attributeSlug, ?string $valueSlug): ?array
    {
        $valueSlug = trim((string)$valueSlug);
        if ($valueSlug === '') {
            return null;
        }

        /** @var array{slug: string, name: string, h1: string|null, seo_title: string|null, seo_description: string|null, intro_text: string|null, bottom_text: string|null, short_description: string|null, is_indexable: int|string|bool}|false $row */
        $row = $this->connection->createQueryBuilder()
            ->select(
                'av.slug',
                'av.name',
                'av.h1',
                'av.seo_title',
                'av.seo_description',
                'av.intro_text',
                'av.bottom_text',
                'av.short_description',
                'av.is_indexable',
            )
            ->from('attribute_values', 'av')
            ->innerJoin('av', 'attributes', 'a', 'a.id = av.attribute_id AND a.slug = :attributeSlug AND a.deleted_at IS NULL')
            ->where('av.slug = :slug')
            ->andWhere('av.deleted_at IS NULL')
            ->setParameter('attributeSlug', $attributeSlug)
            ->setParameter('slug', $valueSlug)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if ($row === false) {
            return null;
        }

        return [
            'slug'              => (string)$row['slug'],
            'name'              => (string)$row['name'],
            'h1'                => $this->nullableText($row['h1']),
            'seo_title'         => $this->nullableText($row['seo_title']),
            'seo_description'   => $this->nullableText($row['seo_description']),
            'intro_text'        => $this->nullableText($row['intro_text']),
            'bottom_text'       => $this->nullableText($row['bottom_text']),
            'short_description' => $this->nullableText($row['short_description']),
            'is_indexable'      => (bool)(int)$row['is_indexable'],
        ];
    }

    /**
     * @throws Exception
     */
    private function normalizeCategory(?string $selectedCategory): ?string
    {
        $category = trim((string)$selectedCategory);

        if ($category === '' || $category === 'all') {
            return null;
        }

        if (ctype_digit($category)) {
            return $category;
        }

        $id = $this->connection->createQueryBuilder()
            ->select('CAST(c.id AS CHAR)')
            ->from('categories', 'c')
            ->where('c.slug = :slug')
            ->andWhere('c.deleted_at IS NULL')
            ->setParameter('slug', $category)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();

        return $id !== false && $id !== null ? (string)$id : $category;
    }

    private function normalizeSearch(?string $search): ?string
    {
        $query = trim((string)$search);

        return $query === '' ? null : mb_substr($query, 0, 80);
    }

    private function applyFilters(
        QueryBuilder $qb,
        ?string $category,
        ?string $search,
        ?string $componentSlug,
        ?string $purposeSlug,
    ): void
    {
        if ($category !== null) {
            $categoryIds = $this->categoryFilterIds($category);
            if ($categoryIds === []) {
                $qb->andWhere('1 = 0');
            } else {
                $qb->andWhere('p.category_id IN (:categoryIds)')
                    ->setParameter('categoryIds', $categoryIds, ArrayParameterType::STRING);
            }
        }

        if ($search !== null) {
            $like = '%' . $this->escapeLike($search) . '%';
            $qb->andWhere(
                '(p.name LIKE :search ESCAPE \'!\' OR p.description LIKE :search ESCAPE \'!\' OR p.short_description LIKE :search ESCAPE \'!\')',
            )->setParameter('search', $like);
        }

        $componentSlug = trim((string)$componentSlug);
        $this->applyAttributeFilter($qb, 'component', 'sostav', $componentSlug);

        $purposeSlug = trim((string)$purposeSlug);
        $this->applyAttributeFilter($qb, 'purpose', 'dlya', $purposeSlug);
    }

    private function applyAttributeFilter(
        QueryBuilder $qb,
        string $name,
        string $attributeSlug,
        string $valueSlug,
    ): void {
        if ($valueSlug === '') {
            return;
        }

        $pavAlias = $name . '_pav';
        $valueAlias = $name . '_av';
        $attributeAlias = $name . '_attr';

        $qb->innerJoin('p', 'product_attribute_values', $pavAlias, "{$pavAlias}.product_id = p.id")
            ->innerJoin($pavAlias, 'attribute_values', $valueAlias, "{$valueAlias}.id = {$pavAlias}.attribute_value_id AND {$valueAlias}.deleted_at IS NULL")
            ->innerJoin($valueAlias, 'attributes', $attributeAlias, "{$attributeAlias}.id = {$valueAlias}.attribute_id AND {$attributeAlias}.slug = :{$name}AttributeSlug AND {$attributeAlias}.deleted_at IS NULL")
            ->andWhere("{$valueAlias}.slug = :{$name}ValueSlug")
            ->setParameter($name . 'AttributeSlug', $attributeSlug)
            ->setParameter($name . 'ValueSlug', $valueSlug);
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

    /**
     * @return array<string, string|null>
     * @throws Exception
     */
    private function categoryParentMap(): array
    {
        /** @var list<array{id: int|string, parent_id: int|string|null}> $rows */
        $rows = $this->connection->createQueryBuilder()
            ->select('CAST(c.id AS CHAR) AS id', 'CAST(c.parent_id AS CHAR) AS parent_id')
            ->from('categories', 'c')
            ->where('c.deleted_at IS NULL')
            ->executeQuery()
            ->fetchAllAssociative();

        $parents = [];
        foreach ($rows as $row) {
            $parents[(string)$row['id']] = $row['parent_id'] !== null && trim((string)$row['parent_id']) !== ''
                ? (string)$row['parent_id']
                : null;
        }

        return $parents;
    }

    /**
     * @return list<string>
     * @throws Exception
     */
    private function categoryFilterIds(string $categoryId): array
    {
        if (!ctype_digit($categoryId)) {
            return [];
        }

        $parents = $this->categoryParentMap();
        if (!array_key_exists($categoryId, $parents)) {
            return [];
        }

        $ids = [$categoryId => $categoryId];
        $queue = [$categoryId];

        while ($queue !== []) {
            $parentId = array_shift($queue);
            foreach ($parents as $id => $currentParentId) {
                if ($currentParentId !== $parentId || isset($ids[$id])) {
                    continue;
                }

                $ids[$id] = $id;
                $queue[] = $id;
            }
        }

        return array_values($ids);
    }

    private function plainText(string $html): string
    {
        return trim((string)preg_replace('/\s+/u', ' ', strip_tags($html)));
    }

    private function nullableText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string)$value);

        return $value === '' ? null : $value;
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

    /**
     * @param list<string> $fallbackImages
     * @return list<string>
     * @throws Exception
     */
    private function productImages(int $productId, string $mainImage, array $fallbackImages): array
    {
        $rows = $this->connection->createQueryBuilder()
            ->select('pi.path')
            ->from('product_images', 'pi')
            ->where('pi.product_id = :productId')
            ->setParameter('productId', $productId)
            ->orderBy('pi.is_main', 'DESC')
            ->addOrderBy('pi.sort_order', 'ASC')
            ->addOrderBy('pi.id', 'ASC')
            ->executeQuery()
            ->fetchFirstColumn();

        $images = array_values(array_filter(
            array_map(static fn (mixed $path): string => trim((string)$path), $rows),
            static fn (string $path): bool => $path !== '',
        ));

        return $this->galleryImages($mainImage, $images !== [] ? $images : $fallbackImages);
    }

    /**
     * @param list<string> $fallbackImages
     * @return list<ProductImageView>
     * @throws Exception
     */
    private function productImageItems(
        int $productId,
        string $mainImage,
        string $mainAlt,
        string $title,
        array $fallbackImages,
    ): array {
        $rows = $this->connection->createQueryBuilder()
            ->select('pi.path', 'pi.alt', 'pi.title', 'pi.width', 'pi.height')
            ->from('product_images', 'pi')
            ->where('pi.product_id = :productId')
            ->setParameter('productId', $productId)
            ->orderBy('pi.is_main', 'DESC')
            ->addOrderBy('pi.sort_order', 'ASC')
            ->addOrderBy('pi.id', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        if ($rows !== []) {
            $images = [];
            foreach ($rows as $row) {
                $path = trim((string)$row['path']);
                if ($path === '') {
                    continue;
                }

                $images[] = new ProductImageView(
                    path: $path,
                    alt: $this->nullableText($row['alt']) ?? $mainAlt,
                    title: $this->nullableText($row['title']) ?? $title,
                    width: $row['width'] !== null ? (int)$row['width'] : null,
                    height: $row['height'] !== null ? (int)$row['height'] : null,
                );
            }

            if ($images !== []) {
                return $images;
            }
        }

        return array_map(
            static fn (string $path): ProductImageView => new ProductImageView($path, $mainAlt, $title),
            $this->galleryImages($mainImage, $fallbackImages),
        );
    }

    /**
     * @return list<ProductVariantView>
     * @throws Exception
     */
    private function productVariants(int $productId): array
    {
        $groupId = $this->connection->fetchOne(
            'SELECT pgi.group_id
             FROM product_group_items pgi
             INNER JOIN product_groups pg ON pg.id = pgi.group_id AND pg.deleted_at IS NULL
             WHERE pgi.product_id = :productId
             LIMIT 1',
            ['productId' => $productId],
        );

        if ($groupId === false || $groupId === null) {
            return [];
        }

        /** @var list<array{id: int|string, slug: string, name: string, image: string, image_alt: string|null, weight: string}> $rows */
        $rows = $this->connection->createQueryBuilder()
            ->select(
                'p.id',
                'p.slug',
                'p.name',
                'COALESCE(pi.path, p.image) AS image',
                'COALESCE(pi.alt, p.image_alt, p.name) AS image_alt',
                'p.weight',
            )
            ->from('product_group_items', 'pgi')
            ->innerJoin('pgi', 'products', 'p', 'p.id = pgi.product_id AND p.deleted_at IS NULL AND p.is_active = 1')
            ->leftJoin('p', 'product_images', 'pi', 'pi.product_id = p.id AND pi.is_main = 1')
            ->where('pgi.group_id = :groupId')
            ->setParameter('groupId', (int)$groupId)
            ->orderBy('p.id', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        if (\count($rows) <= 1) {
            return [];
        }

        return array_map(function (array $row) use ($productId): ProductVariantView {
            $weight = trim((string)$row['weight']);

            return new ProductVariantView(
                id: (int)$row['id'],
                slug: (string)$row['slug'],
                title: (string)$row['name'],
                image: (string)$row['image'],
                imageAlt: $this->nullableText($row['image_alt']) ?? (string)$row['name'],
                label: $weight !== '' ? $weight : (string)$row['name'],
                weight: $weight,
                isCurrent: (int)$row['id'] === $productId,
            );
        }, $rows);
    }

    /**
     * @return array{rate: float, count: int}
     * @throws Exception
     */
    private function rating(int $productId): array
    {
        /** @var array{rating_rate: string|null, rating_count: int|string}|false $row */
        $row = $this->connection->createQueryBuilder()
            ->select('AVG(r.rating) AS rating_rate', 'COUNT(r.id) AS rating_count')
            ->from('reviews', 'r')
            ->where('r.product_id = :productId')
            ->andWhere('r.deleted_at IS NULL')
            ->andWhere('r.is_approved = 1')
            ->setParameter('productId', $productId)
            ->executeQuery()
            ->fetchAssociative();

        if ($row === false || (int)$row['rating_count'] === 0) {
            return ['rate' => 0.0, 'count' => 0];
        }

        return [
            'rate'  => round((float)$row['rating_rate'], 1),
            'count' => (int)$row['rating_count'],
        ];
    }
}
