<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Override;

final class Version20260710175500 extends AbstractMigration
{
    #[Override]
    public function getDescription(): string
    {
        return 'Seed base product categories used by imported products.';
    }

    #[Override]
    public function up(Schema $schema): void
    {
        $this->addSql(
            "INSERT INTO categories (id, slug, name, created_at, updated_at, deleted_at) VALUES
                (1, 'ready-products', 'Готовая оздоровительная продукция', UTC_TIMESTAMP(), NULL, NULL),
                (2, 'raw-materials', 'Сырье для изготовления оздоровительной продукции', UTC_TIMESTAMP(), NULL, NULL)
            ON DUPLICATE KEY UPDATE
                slug = VALUES(slug),
                name = VALUES(name),
                deleted_at = NULL"
        );
    }

    #[Override]
    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM categories WHERE id IN (1, 2) AND slug IN ('ready-products', 'raw-materials')");
    }
}
