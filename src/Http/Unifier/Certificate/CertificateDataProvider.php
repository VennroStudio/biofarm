<?php

declare(strict_types=1);

namespace App\Http\Unifier\Certificate;

use App\Http\View\Certificate\CertificateView;
use App\Modules\Content\Service\MaterialLibrary;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ArrayParameterType;

final readonly class CertificateDataProvider
{
    public function __construct(private MaterialLibrary $materials, private Connection $connection) {}

    /** @return list<CertificateView> */
    public function forPage(string $scope, ?string $key = null): array
    {
        $rows = $this->materials->publicItems('certificate', $scope, $key);
        $products = [];
        if ($rows !== []) {
            $links = $this->connection->fetchAllAssociative(
                "SELECT mp.material_id,p.id,p.name,p.slug FROM material_placements mp JOIN products p ON p.id=mp.target_id AND p.deleted_at IS NULL WHERE mp.kind='certificate' AND mp.target_type='product' AND mp.material_id IN (?) ORDER BY p.name,p.id",
                [array_column($rows, 'id')],
                [ArrayParameterType::INTEGER],
            );
            foreach ($links as $link) { $products[(int)$link['material_id']][] = $link; }
        }
        return array_map(
            static fn (array $row): CertificateView => new CertificateView(
                id: (int)$row['id'],
                title: (string)$row['title'],
                filePath: (string)$row['file_path'],
                documentType: (string)$row['document_type'],
                productId: count($products[(int)$row['id']] ?? []) === 1 ? (int)$products[(int)$row['id']][0]['id'] : null,
                productName: isset($products[(int)$row['id']]) ? implode(', ', array_column($products[(int)$row['id']], 'name')) : null,
                productSlug: count($products[(int)$row['id']] ?? []) === 1 ? $products[(int)$row['id']][0]['slug'] : null,
                description: $row['description'] ?? null,
            ),
            $rows,
        );
    }
}
