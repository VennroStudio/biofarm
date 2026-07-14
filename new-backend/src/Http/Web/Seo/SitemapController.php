<?php

declare(strict_types=1);

namespace App\Http\Web\Seo;

use App\Components\Seo\SeoUrlGenerator;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class SitemapController implements RequestHandlerInterface
{
    private const array SYSTEM_PAGE_PATHS = [
        'home'          => '/',
        'catalog'       => '/catalog',
        'blog'          => '/blog',
        'privacy'       => '/privacy',
        'oferta'        => '/oferta',
        'cart'          => '/cart',
        'checkout'      => '/checkout',
        'order_success' => '/order-success',
        'login'         => '/login',
        'profile'       => '/profile',
    ];

    public function __construct(
        private Connection $connection,
        private SeoUrlGenerator $urls,
        private ResponseFactoryInterface $responseFactory,
    ) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $xml = $this->xml($this->items());

        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->getBody()->write($xml);

        return $response;
    }

    /**
     * @return list<array{loc: string, lastmod: string|null, priority: string}>
     * @throws Exception
     */
    private function items(): array
    {
        return [
            ...$this->pageItems(),
            ...$this->categoryItems(),
            ...$this->componentItems(),
            ...$this->purposeItems(),
            ...$this->productItems(),
            ...$this->blogItems(),
        ];
    }

    /**
     * @return list<array{loc: string, lastmod: string|null, priority: string}>
     * @throws Exception
     */
    private function pageItems(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT page_type, system_key, slug_path, updated_at, published_at, created_at
             FROM pages
             WHERE deleted_at IS NULL
               AND is_published = 1
               AND is_indexable = 1
               AND show_in_sitemap = 1
             ORDER BY sort_order ASC, title ASC'
        );

        $items = [];
        foreach ($rows as $row) {
            $loc = $this->pageLocation($row);
            if ($loc === null) {
                continue;
            }

            $items[] = [
                'loc'      => $loc,
                'lastmod'  => self::lastmod((string)($row['updated_at'] ?? $row['published_at'] ?? $row['created_at'] ?? '')) ?? $this->today(),
                'priority' => $this->pagePriority($loc, (string)$row['page_type']),
            ];
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function pageLocation(array $row): ?string
    {
        if ((string)$row['page_type'] === 'system') {
            $systemKey = (string)($row['system_key'] ?? '');

            return self::SYSTEM_PAGE_PATHS[$systemKey] ?? null;
        }

        $slugPath = trim((string)($row['slug_path'] ?? ''), '/');

        return $slugPath !== '' ? '/' . $slugPath : null;
    }

    private function pagePriority(string $loc, string $pageType): string
    {
        return match (true) {
            $loc === '/' => '1.0',
            $loc === '/catalog' => '0.9',
            $loc === '/blog' => '0.7',
            $pageType === 'custom' => '0.5',
            default => '0.3',
        };
    }

    /**
     * @return list<array{loc: string, lastmod: string|null, priority: string}>
     * @throws Exception
     */
    private function categoryItems(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT c.slug, c.updated_at, c.created_at, p.slug AS parent_slug
             FROM categories c
             LEFT JOIN categories p ON p.id = c.parent_id AND p.deleted_at IS NULL
             WHERE c.deleted_at IS NULL AND c.is_indexable = 1
             ORDER BY c.parent_id IS NOT NULL, c.sort_order ASC, c.name ASC'
        );

        return array_map(static fn (array $row): array => [
            'loc'      => $row['parent_slug'] !== null
                ? '/catalog/' . $row['parent_slug'] . '/' . $row['slug']
                : '/catalog/' . $row['slug'],
            'lastmod'  => self::lastmod((string)($row['updated_at'] ?? $row['created_at'] ?? '')),
            'priority' => $row['parent_slug'] !== null ? '0.7' : '0.8',
        ], $rows);
    }

    /**
     * @return list<array{loc: string, lastmod: string|null, priority: string}>
     * @throws Exception
     */
    private function componentItems(): array
    {
        return $this->attributeItems('sostav');
    }

    /**
     * @return list<array{loc: string, lastmod: string|null, priority: string}>
     * @throws Exception
     */
    private function purposeItems(): array
    {
        return $this->attributeItems('dlya');
    }

    /**
     * @return list<array{loc: string, lastmod: string|null, priority: string}>
     * @throws Exception
     */
    private function attributeItems(string $filterPrefix): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT DISTINCT c.slug AS category_slug, parent.slug AS parent_slug, a.filter_prefix, av.slug AS value_slug, av.updated_at, av.created_at
             FROM attribute_values av
             INNER JOIN attributes a ON a.id = av.attribute_id AND a.deleted_at IS NULL
             INNER JOIN product_attribute_values pav ON pav.attribute_value_id = av.id
             INNER JOIN products p ON p.id = pav.product_id AND p.deleted_at IS NULL AND p.is_active = 1
             INNER JOIN categories c ON c.id = CAST(p.category_id AS UNSIGNED) AND c.deleted_at IS NULL AND c.is_indexable = 1
             LEFT JOIN categories parent ON parent.id = c.parent_id AND parent.deleted_at IS NULL
             WHERE a.filter_prefix = :filterPrefix AND av.is_indexable = 1 AND av.deleted_at IS NULL
             ORDER BY c.slug ASC, av.sort_order ASC, av.name ASC',
            ['filterPrefix' => $filterPrefix],
        );

        return array_map(static fn (array $row): array => [
            'loc'      => '/catalog/'
                . ($row['parent_slug'] !== null ? $row['parent_slug'] . '/' : '')
                . $row['category_slug']
                . '/'
                . $row['filter_prefix']
                . '/'
                . $row['value_slug'],
            'lastmod'  => self::lastmod((string)($row['updated_at'] ?? $row['created_at'] ?? '')),
            'priority' => '0.6',
        ], $rows);
    }

    /**
     * @return list<array{loc: string, lastmod: string|null, priority: string}>
     * @throws Exception
     */
    private function productItems(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT slug, updated_at, created_at
             FROM products
             WHERE deleted_at IS NULL AND is_active = 1
             ORDER BY created_at DESC, id DESC'
        );

        return array_map(static fn (array $row): array => [
            'loc'      => '/product/' . $row['slug'],
            'lastmod'  => self::lastmod((string)($row['updated_at'] ?? $row['created_at'] ?? '')),
            'priority' => '0.8',
        ], $rows);
    }

    /**
     * @return list<array{loc: string, lastmod: string|null, priority: string}>
     * @throws Exception
     */
    private function blogItems(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT slug, updated_at, published_at, created_at
             FROM blog_posts
             WHERE deleted_at IS NULL AND is_published = 1
             ORDER BY COALESCE(published_at, created_at) DESC, id DESC'
        );

        return array_map(static fn (array $row): array => [
            'loc'      => '/blog/' . $row['slug'],
            'lastmod'  => self::lastmod((string)($row['updated_at'] ?? $row['published_at'] ?? $row['created_at'] ?? '')),
            'priority' => '0.6',
        ], $rows);
    }

    /**
     * @param list<array{loc: string, lastmod: string|null, priority: string}> $items
     */
    private function xml(array $items): string
    {
        $urls = array_map(function (array $item): string {
            $loc = htmlspecialchars($this->urls->absolute($item['loc']), ENT_XML1);
            $lastmod = $item['lastmod'] !== null
                ? "\n        <lastmod>{$item['lastmod']}</lastmod>"
                : '';

            return "    <url>\n        <loc>{$loc}</loc>{$lastmod}\n        <priority>{$item['priority']}</priority>\n    </url>";
        }, $items);

        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n"
            . implode("\n", $urls)
            . "\n</urlset>\n";
    }

    private function today(): string
    {
        return (new DateTimeImmutable())->format('Y-m-d');
    }

    private static function lastmod(string $value): ?string
    {
        if (trim($value) === '') {
            return null;
        }

        $time = strtotime($value);

        return $time === false ? null : date('Y-m-d', $time);
    }
}
