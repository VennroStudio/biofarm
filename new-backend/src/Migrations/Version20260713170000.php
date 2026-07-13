<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Override;

final class Version20260713170000 extends AbstractMigration
{
    #[Override]
    public function getDescription(): string
    {
        return 'Add generic product attributes and product variant groups.';
    }

    #[Override]
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE attributes (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, filter_prefix VARCHAR(100) DEFAULT NULL, is_filterable TINYINT DEFAULT 1 NOT NULL, is_indexable TINYINT DEFAULT 1 NOT NULL, show_on_product TINYINT DEFAULT 1 NOT NULL, sort_order INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX uniq_attributes_slug (slug), UNIQUE INDEX uniq_attributes_filter_prefix (filter_prefix), INDEX idx_attributes_filterable (is_filterable), INDEX idx_attributes_sort_order (sort_order), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE attribute_values (id INT AUTO_INCREMENT NOT NULL, attribute_id INT NOT NULL, slug VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, h1 VARCHAR(255) DEFAULT NULL, seo_title VARCHAR(255) DEFAULT NULL, seo_description VARCHAR(500) DEFAULT NULL, intro_text LONGTEXT DEFAULT NULL, bottom_text LONGTEXT DEFAULT NULL, short_description VARCHAR(500) DEFAULT NULL, synonyms JSON DEFAULT NULL, is_indexable TINYINT DEFAULT 1 NOT NULL, sort_order INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX uniq_attribute_values_attribute_slug (attribute_id, slug), INDEX idx_attribute_values_attribute_id (attribute_id), INDEX idx_attribute_values_slug (slug), INDEX idx_attribute_values_sort_order (sort_order), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product_attribute_values (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, attribute_value_id INT NOT NULL, value_text VARCHAR(255) DEFAULT NULL, sort_order INT DEFAULT 0 NOT NULL, INDEX idx_product_attribute_values_product_id (product_id), INDEX idx_product_attribute_values_value_id (attribute_value_id), UNIQUE INDEX uniq_product_attribute_values_product_value (product_id, attribute_value_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product_groups (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, sort_order INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX uniq_product_groups_slug (slug), INDEX idx_product_groups_sort_order (sort_order), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product_group_items (id INT AUTO_INCREMENT NOT NULL, group_id INT NOT NULL, product_id INT NOT NULL, variant_label VARCHAR(255) DEFAULT NULL, sort_order INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX idx_product_group_items_group_id (group_id), UNIQUE INDEX uniq_product_group_items_product (product_id), UNIQUE INDEX uniq_product_group_items_group_product (group_id, product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql(
            "INSERT INTO attributes (slug, name, filter_prefix, is_filterable, is_indexable, show_on_product, sort_order, created_at) VALUES
                ('sostav', 'Состав', 'sostav', 1, 1, 1, 10, UTC_TIMESTAMP()),
                ('dlya', 'Для чего', 'dlya', 1, 1, 1, 20, UTC_TIMESTAMP())
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                filter_prefix = VALUES(filter_prefix),
                is_filterable = VALUES(is_filterable),
                is_indexable = VALUES(is_indexable),
                show_on_product = VALUES(show_on_product),
                sort_order = VALUES(sort_order),
                deleted_at = NULL"
        );

        $this->addSql(
            "INSERT INTO attribute_values (attribute_id, slug, name, short_description, synonyms, seo_title, seo_description, intro_text, is_indexable, sort_order, created_at, updated_at, deleted_at)
             SELECT a.id, c.slug, c.name, c.short_description, c.synonyms, c.seo_title, c.seo_description, c.intro_text, c.is_indexable, c.sort_order, COALESCE(c.created_at, UTC_TIMESTAMP()), c.updated_at, c.deleted_at
             FROM components c
             INNER JOIN attributes a ON a.slug = 'sostav'
             ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                short_description = VALUES(short_description),
                synonyms = VALUES(synonyms),
                seo_title = VALUES(seo_title),
                seo_description = VALUES(seo_description),
                intro_text = VALUES(intro_text),
                is_indexable = VALUES(is_indexable),
                sort_order = VALUES(sort_order),
                updated_at = VALUES(updated_at),
                deleted_at = VALUES(deleted_at)"
        );

        $this->addSql(
            "INSERT INTO attribute_values (attribute_id, slug, name, h1, seo_title, seo_description, intro_text, bottom_text, is_indexable, sort_order, created_at, updated_at, deleted_at)
             SELECT a.id, p.slug, p.name, p.h1, p.seo_title, p.seo_description, p.intro_text, p.bottom_text, p.is_indexable, p.sort_order, COALESCE(p.created_at, UTC_TIMESTAMP()), p.updated_at, p.deleted_at
             FROM product_purposes p
             INNER JOIN attributes a ON a.slug = 'dlya'
             ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                h1 = VALUES(h1),
                seo_title = VALUES(seo_title),
                seo_description = VALUES(seo_description),
                intro_text = VALUES(intro_text),
                bottom_text = VALUES(bottom_text),
                is_indexable = VALUES(is_indexable),
                sort_order = VALUES(sort_order),
                updated_at = VALUES(updated_at),
                deleted_at = VALUES(deleted_at)"
        );

        $this->addSql(
            "INSERT IGNORE INTO product_attribute_values (product_id, attribute_value_id, value_text, sort_order)
             SELECT pc.product_id, av.id, pc.amount_text, pc.sort_order
             FROM product_components pc
             INNER JOIN components c ON c.id = pc.component_id
             INNER JOIN attributes a ON a.slug = 'sostav'
             INNER JOIN attribute_values av ON av.attribute_id = a.id AND av.slug = c.slug"
        );

        $this->addSql(
            "INSERT IGNORE INTO product_attribute_values (product_id, attribute_value_id, value_text, sort_order)
             SELECT ppr.product_id, av.id, NULL, ppr.sort_order
             FROM product_purpose_relations ppr
             INNER JOIN product_purposes pp ON pp.id = ppr.purpose_id
             INNER JOIN attributes a ON a.slug = 'dlya'
             INNER JOIN attribute_values av ON av.attribute_id = a.id AND av.slug = pp.slug"
        );
    }

    #[Override]
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE product_group_items');
        $this->addSql('DROP TABLE product_groups');
        $this->addSql('DROP TABLE product_attribute_values');
        $this->addSql('DROP TABLE attribute_values');
        $this->addSql('DROP TABLE attributes');
    }
}
