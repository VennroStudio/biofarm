<?php

declare(strict_types=1);

namespace App\Modules\Page\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class PageNavigationProvider
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
    ) {}

    /**
     * @return list<array{label: string, href: string, external: bool}>
     */
    public function headerPages(): array
    {
        return $this->pagesFor('show_in_header');
    }

    /**
     * @return list<array{label: string, href: string, external: bool}>
     */
    public function footerPages(): array
    {
        return $this->pagesFor('show_in_footer');
    }

    /**
     * @return list<array{label: string, href: string, external: bool}>
     */
    private function pagesFor(string $column): array
    {
        if (!\in_array($column, ['show_in_header', 'show_in_footer'], true)) {
            return [];
        }

        try {
            $rows = $this->connection->fetchAllAssociative(
                "SELECT page_type, system_key, slug_path, title, h1
                 FROM pages
                 WHERE deleted_at IS NULL
                   AND is_published = 1
                   AND {$column} = 1
                 ORDER BY sort_order ASC, title ASC"
            );
        } catch (Exception) {
            return [];
        }

        $items = [];
        foreach ($rows as $row) {
            $href = $this->href($row);
            if ($href === null) {
                continue;
            }

            $items[] = [
                'label'    => $this->label($row),
                'href'     => $href,
                'external' => false,
            ];
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function href(array $row): ?string
    {
        if ((string)$row['page_type'] === 'system') {
            return self::SYSTEM_PAGE_PATHS[(string)($row['system_key'] ?? '')] ?? null;
        }

        $slugPath = trim((string)($row['slug_path'] ?? ''), '/');

        return $slugPath !== '' ? '/' . $slugPath : null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function label(array $row): string
    {
        $h1 = trim((string)($row['h1'] ?? ''));

        return $h1 !== '' ? $h1 : (string)$row['title'];
    }
}
