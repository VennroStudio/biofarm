<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Override;

final class Version20260713182000 extends AbstractMigration
{
    #[Override]
    public function getDescription(): string
    {
        return 'Simplify product groups to named product bindings only.';
    }

    #[Override]
    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_product_groups_slug ON product_groups');
        $this->addSql('DROP INDEX idx_product_groups_sort_order ON product_groups');
        $this->addSql('ALTER TABLE product_groups DROP slug, DROP sort_order');
        $this->addSql('ALTER TABLE product_group_items DROP variant_label, DROP sort_order');
    }

    #[Override]
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product_groups ADD slug VARCHAR(255) DEFAULT NULL, ADD sort_order INT DEFAULT 0 NOT NULL');
        $this->addSql("UPDATE product_groups SET slug = CONCAT('group-', id) WHERE slug IS NULL OR slug = ''");
        $this->addSql('ALTER TABLE product_groups MODIFY slug VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_product_groups_slug ON product_groups (slug)');
        $this->addSql('CREATE INDEX idx_product_groups_sort_order ON product_groups (sort_order)');
        $this->addSql('ALTER TABLE product_group_items ADD variant_label VARCHAR(255) DEFAULT NULL, ADD sort_order INT DEFAULT 0 NOT NULL');
    }
}
