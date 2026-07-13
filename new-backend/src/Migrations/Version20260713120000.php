<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Override;

final class Version20260713120000 extends AbstractMigration
{
    #[Override]
    public function getDescription(): string
    {
        return 'Add SEO fields, product facets, product images and blog categories.';
    }

    #[Override]
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE categories ADD parent_id INT DEFAULT NULL, ADD h1 VARCHAR(255) DEFAULT NULL, ADD seo_title VARCHAR(255) DEFAULT NULL, ADD seo_description VARCHAR(500) DEFAULT NULL, ADD intro_text LONGTEXT DEFAULT NULL, ADD bottom_text LONGTEXT DEFAULT NULL, ADD image VARCHAR(500) DEFAULT NULL, ADD is_indexable TINYINT DEFAULT 1 NOT NULL, ADD sort_order INT DEFAULT 0 NOT NULL');
        $this->addSql('CREATE INDEX idx_categories_parent_id ON categories (parent_id)');
        $this->addSql('CREATE INDEX idx_categories_is_indexable ON categories (is_indexable)');
        $this->addSql('CREATE INDEX idx_categories_sort_order ON categories (sort_order)');

        $this->addSql('ALTER TABLE products ADD h1 VARCHAR(255) DEFAULT NULL, ADD seo_title VARCHAR(255) DEFAULT NULL, ADD seo_description VARCHAR(500) DEFAULT NULL, ADD image_alt VARCHAR(500) DEFAULT NULL, ADD sku VARCHAR(100) DEFAULT NULL, ADD gtin VARCHAR(100) DEFAULT NULL, ADD availability VARCHAR(50) DEFAULT \'in_stock\' NOT NULL, ADD published_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_products_availability ON products (availability)');
        $this->addSql('CREATE INDEX idx_products_published_at ON products (published_at)');

        $this->addSql('ALTER TABLE blog_posts ADD h1 VARCHAR(255) DEFAULT NULL, ADD seo_title VARCHAR(255) DEFAULT NULL, ADD seo_description VARCHAR(500) DEFAULT NULL, ADD image_alt VARCHAR(500) DEFAULT NULL, ADD published_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_blog_posts_published_at ON blog_posts (published_at)');

        $this->addSql('ALTER TABLE media_assets ADD alt VARCHAR(500) DEFAULT NULL, ADD title VARCHAR(255) DEFAULT NULL, ADD caption VARCHAR(500) DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL');

        $this->addSql('CREATE TABLE components (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, synonyms JSON DEFAULT NULL, short_description VARCHAR(500) DEFAULT NULL, seo_title VARCHAR(255) DEFAULT NULL, seo_description VARCHAR(500) DEFAULT NULL, intro_text LONGTEXT DEFAULT NULL, is_indexable TINYINT DEFAULT 1 NOT NULL, sort_order INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX uniq_components_slug (slug), INDEX idx_components_is_indexable (is_indexable), INDEX idx_components_sort_order (sort_order), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE product_components (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, component_id INT NOT NULL, amount_text VARCHAR(255) DEFAULT NULL, sort_order INT DEFAULT 0 NOT NULL, INDEX idx_product_components_product_id (product_id), INDEX idx_product_components_component_id (component_id), UNIQUE INDEX uniq_product_components_product_component (product_id, component_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE product_purposes (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, h1 VARCHAR(255) DEFAULT NULL, seo_title VARCHAR(255) DEFAULT NULL, seo_description VARCHAR(500) DEFAULT NULL, intro_text LONGTEXT DEFAULT NULL, bottom_text LONGTEXT DEFAULT NULL, is_indexable TINYINT DEFAULT 1 NOT NULL, sort_order INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX uniq_product_purposes_slug (slug), INDEX idx_product_purposes_is_indexable (is_indexable), INDEX idx_product_purposes_sort_order (sort_order), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE product_purpose_relations (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, purpose_id INT NOT NULL, sort_order INT DEFAULT 0 NOT NULL, INDEX idx_product_purpose_relations_product_id (product_id), INDEX idx_product_purpose_relations_purpose_id (purpose_id), UNIQUE INDEX uniq_product_purpose_relations_product_purpose (product_id, purpose_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE product_images (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, path VARCHAR(500) NOT NULL, alt VARCHAR(500) DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, sort_order INT DEFAULT 0 NOT NULL, is_main TINYINT DEFAULT 0 NOT NULL, width INT DEFAULT NULL, height INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX idx_product_images_product_id (product_id), INDEX idx_product_images_is_main (is_main), INDEX idx_product_images_sort_order (sort_order), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE blog_categories (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, h1 VARCHAR(255) DEFAULT NULL, seo_title VARCHAR(255) DEFAULT NULL, seo_description VARCHAR(500) DEFAULT NULL, intro_text LONGTEXT DEFAULT NULL, is_indexable TINYINT DEFAULT 1 NOT NULL, sort_order INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX uniq_blog_categories_slug (slug), INDEX idx_blog_categories_is_indexable (is_indexable), INDEX idx_blog_categories_sort_order (sort_order), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql(
            "INSERT INTO blog_categories (slug, name, h1, is_indexable, sort_order, created_at) VALUES
                ('health', 'Здоровье', 'Здоровье', 1, 10, UTC_TIMESTAMP()),
                ('tips', 'Советы', 'Советы', 1, 20, UTC_TIMESTAMP()),
                ('recipes', 'Рецепты', 'Рецепты', 1, 30, UTC_TIMESTAMP()),
                ('about', 'О нас', 'О нас', 1, 40, UTC_TIMESTAMP())
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                h1 = VALUES(h1),
                deleted_at = NULL"
        );

        $this->addSql("INSERT INTO product_images (product_id, path, alt, title, sort_order, is_main, created_at) SELECT id, image, name, name, 0, 1, UTC_TIMESTAMP() FROM products WHERE image <> ''");
        $this->addSql("INSERT INTO product_images (product_id, path, alt, title, sort_order, is_main, created_at) SELECT p.id, jt.image_path, p.name, p.name, jt.sort_order, 0, UTC_TIMESTAMP() FROM products p JOIN JSON_TABLE(p.images, '$[*]' COLUMNS (sort_order FOR ORDINALITY, image_path VARCHAR(500) PATH '$')) jt WHERE p.images IS NOT NULL AND jt.image_path <> '' AND jt.image_path <> p.image");
    }

    #[Override]
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE blog_categories');
        $this->addSql('DROP TABLE product_images');
        $this->addSql('DROP TABLE product_purpose_relations');
        $this->addSql('DROP TABLE product_purposes');
        $this->addSql('DROP TABLE product_components');
        $this->addSql('DROP TABLE components');

        $this->addSql('ALTER TABLE media_assets DROP alt, DROP title, DROP caption, DROP updated_at');
        $this->addSql('DROP INDEX idx_blog_posts_published_at ON blog_posts');
        $this->addSql('ALTER TABLE blog_posts DROP h1, DROP seo_title, DROP seo_description, DROP image_alt, DROP published_at');
        $this->addSql('DROP INDEX idx_products_published_at ON products');
        $this->addSql('DROP INDEX idx_products_availability ON products');
        $this->addSql('ALTER TABLE products DROP h1, DROP seo_title, DROP seo_description, DROP image_alt, DROP sku, DROP gtin, DROP availability, DROP published_at');
        $this->addSql('DROP INDEX idx_categories_sort_order ON categories');
        $this->addSql('DROP INDEX idx_categories_is_indexable ON categories');
        $this->addSql('DROP INDEX idx_categories_parent_id ON categories');
        $this->addSql('ALTER TABLE categories DROP parent_id, DROP h1, DROP seo_title, DROP seo_description, DROP intro_text, DROP bottom_text, DROP image, DROP is_indexable, DROP sort_order');
    }
}
