<?php

declare(strict_types=1);

namespace App\Http\Unifier\Certificate;

use App\Components\Seo\JsonLdFactory;
use App\Components\Seo\SeoUrlGenerator;
use App\Http\Unifier\Faq\FaqDataProvider;
use App\Http\View\Certificate\CertificatePageView;
use App\Http\View\Certificate\CertificateView;
use App\Http\View\PageMetaView;
use App\Modules\Page\Service\PageSeoProvider;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class CertificatePageUnifier
{
    public function __construct(
        private Connection $connection,
        private SeoUrlGenerator $urls,
        private JsonLdFactory $jsonLd,
        private PageSeoProvider $pages,
        private FaqDataProvider $faq,
    ) {}

    /**
     * @throws Exception
     */
    public function unify(): CertificatePageView
    {
        $certificates = $this->certificates();
        $faqItems = $this->faq->forPage('certificates');
        $title = 'Сертификаты качества — БИОФАРМ';
        $description = 'Сертификаты и документы на натуральную продукцию БИОФАРМ.';

        $jsonLd = [
            $this->jsonLd->breadcrumbs([
                ['name' => 'Главная', 'url' => '/'],
                ['name' => 'Сертификаты', 'url' => '/certificates'],
            ]),
            $this->jsonLd->webPage('Сертификаты качества', $description, '/certificates'),
        ];

        if ($faqItems !== []) {
            $jsonLd[] = $this->jsonLd->faqPage($faqItems);
        }

        return new CertificatePageView(
            meta: $this->pages->applySystem('certificates', new PageMetaView(
                title: $title,
                description: $description,
                canonicalUrl: $this->urls->absolute('/certificates'),
                robots: 'index, follow',
                ogTitle: $title,
                ogDescription: $description,
                ogImage: null,
                ogImageAlt: 'Сертификаты БИОФАРМ',
                jsonLd: $jsonLd,
            )),
            certificates: $certificates,
            faqItems: $faqItems,
        );
    }

    /**
     * @return list<CertificateView>
     * @throws Exception
     */
    private function certificates(): array
    {
        if (!$this->hasTable('certificates')) {
            return [];
        }

        /** @var list<array{id: int|string, title: string, file_path: string, document_type: string, product_id: int|string|null, product_name: string|null, product_slug: string|null, description: string|null}> $rows */
        $rows = $this->connection->createQueryBuilder()
            ->select(
                'c.id',
                'c.title',
                'c.file_path',
                'c.document_type',
                'c.product_id',
                'p.name AS product_name',
                'p.slug AS product_slug',
                'c.description',
            )
            ->from('certificates', 'c')
            ->leftJoin('c', 'products', 'p', 'p.id = c.product_id AND p.deleted_at IS NULL')
            ->where('c.is_active = 1')
            ->orderBy('c.sort_order', 'ASC')
            ->addOrderBy('c.id', 'DESC')
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map(
            static fn (array $row): CertificateView => new CertificateView(
                id: (int)$row['id'],
                title: (string)$row['title'],
                filePath: (string)$row['file_path'],
                documentType: (string)$row['document_type'],
                productId: $row['product_id'] !== null ? (int)$row['product_id'] : null,
                productName: $row['product_name'] !== null ? (string)$row['product_name'] : null,
                productSlug: $row['product_slug'] !== null ? (string)$row['product_slug'] : null,
                description: $row['description'] !== null && trim((string)$row['description']) !== '' ? (string)$row['description'] : null,
            ),
            $rows,
        );
    }

    /**
     * @throws Exception
     */
    private function hasTable(string $name): bool
    {
        return $this->connection->createSchemaManager()->tablesExist([$name]);
    }
}
