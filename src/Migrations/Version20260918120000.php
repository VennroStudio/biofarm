<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260918120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Central material libraries and ordered reusable target placements, preserving legacy selections.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE material_categories (id INT AUTO_INCREMENT PRIMARY KEY, kind VARCHAR(20) NOT NULL, name VARCHAR(255) NOT NULL, UNIQUE KEY uniq_material_category (kind, name)) DEFAULT CHARACTER SET utf8mb4');
        foreach (['certificates', 'faq_items'] as $table) {
            $this->addSql("ALTER TABLE {$table} ADD category_id INT DEFAULT NULL, ADD INDEX idx_{$table}_category (category_id), ADD CONSTRAINT fk_{$table}_category FOREIGN KEY (category_id) REFERENCES material_categories(id)");
        }
        $this->addSql('CREATE TABLE material_placements (kind VARCHAR(20) NOT NULL, material_id INT NOT NULL, target_type VARCHAR(20) NOT NULL, target_id VARCHAR(255) NOT NULL, sort_order INT NOT NULL DEFAULT 0, PRIMARY KEY(kind, material_id, target_type, target_id), INDEX idx_material_target(kind, target_type, target_id, sort_order)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE material_target_selections (kind VARCHAR(20) NOT NULL, target_type VARCHAR(20) NOT NULL, target_id VARCHAR(255) NOT NULL, PRIMARY KEY(kind, target_type, target_id)) DEFAULT CHARACTER SET utf8mb4');
        $pages = $this->connection->fetchAllAssociative('SELECT id,system_key,slug_path FROM pages WHERE deleted_at IS NULL');
        $products = $this->connection->fetchAllAssociative('SELECT id,slug FROM products WHERE deleted_at IS NULL');
        $certificates = $this->connection->fetchAllAssociative('SELECT id,product_id,is_active FROM certificates ORDER BY sort_order ASC,id DESC');
        $homeCount = 0;
        foreach ($certificates as $order => $row) {
            if ($row['product_id'] !== null && \in_array((int)$row['product_id'], array_map('intval', array_column($products, 'id')), true)) {
                $this->addSql("INSERT INTO material_placements VALUES ('certificate', ?, 'product', ?, ?)", [(int)$row['id'], (string)$row['product_id'], $order]);
            }
            foreach ($pages as $page) {
                if ($page['system_key'] === 'certificates' || ($page['system_key'] === 'home' && (bool)$row['is_active'] && $homeCount < 3)) {
                    $this->addSql("INSERT INTO material_placements VALUES ('certificate', ?, 'page', ?, ?)", [(int)$row['id'], (string)$page['id'], $order]);
                }
            }
            if ((bool)$row['is_active']) {
                ++$homeCount;
            }
        }
        // Keep each legacy rule, including scopes for dynamic pages, as an explicit removable placement.
        foreach ($this->connection->fetchAllAssociative('SELECT id,page_scope,page_id,sort_order FROM faq_items') as $row) {
            $keys = $row['page_scope'] === 'global' || trim((string)$row['page_id']) === '' ? [''] : preg_split('/[,;\r\n]+/', (string)$row['page_id']);
            foreach (array_unique($keys) as $key) {
                $key = trim($key);
                $targetType = 'scope';
                $target = $row['page_scope'] . ($key !== '' ? ':' . $key : '');
                if ($key !== '' && $row['page_scope'] === 'product') {
                    foreach ($products as $product) {
                        if ($key === (string)$product['id'] || $key === $product['slug']) {
                            $targetType = 'product';
                            $target = (string)$product['id'];
                            break;
                        }
                    }
                } elseif ($key !== '' && $row['page_scope'] === 'page') {
                    foreach ($pages as $page) {
                        if ($key === (string)$page['id'] || trim($key, '/') === trim((string)$page['slug_path'], '/')) {
                            $targetType = 'page';
                            $target = (string)$page['id'];
                            break;
                        }
                    }
                }
                $this->addSql("INSERT IGNORE INTO material_placements VALUES ('faq', ?, ?, ?, ?)", [(int)$row['id'], $targetType, $target, (int)$row['sort_order']]);
            }
        }
        $this->addSql("INSERT INTO material_target_selections SELECT DISTINCT kind,target_type,target_id FROM material_placements WHERE kind='certificate'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Reusable placements cannot be represented in legacy single-target columns without data loss. Restore the pre-migration backup instead.');
    }
}
