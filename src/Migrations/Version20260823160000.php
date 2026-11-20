<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Override;

final class Version20260823160000 extends AbstractMigration
{
    #[Override]
    public function getDescription(): string
    {
        return 'Add order pricing, promo, favorites, certificates, FAQ and product BAD fields.';
    }

    #[Override]
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders ADD subtotal INT DEFAULT 0 NOT NULL, ADD delivery_method VARCHAR(30) DEFAULT NULL, ADD delivery_cost INT DEFAULT 0 NOT NULL, ADD discount_amount INT DEFAULT 0 NOT NULL, ADD promo_code VARCHAR(100) DEFAULT NULL');
        $this->addSql('UPDATE orders SET subtotal = total WHERE subtotal = 0');
        $this->addSql('CREATE INDEX idx_orders_promo_code ON orders (promo_code)');

        $this->addSql('CREATE TABLE promo_codes (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(100) NOT NULL, type VARCHAR(20) NOT NULL, value INT NOT NULL, min_order_total INT DEFAULT 0 NOT NULL, starts_at DATETIME DEFAULT NULL, ends_at DATETIME DEFAULT NULL, usage_limit INT DEFAULT NULL, used_count INT DEFAULT 0 NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX uniq_promo_codes_code (code), INDEX idx_promo_codes_active (is_active), INDEX idx_promo_codes_dates (starts_at, ends_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE promo_code_redemptions (id INT AUTO_INCREMENT NOT NULL, promo_code_id INT NOT NULL, order_id VARCHAR(50) NOT NULL, user_id INT NOT NULL, discount_amount INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX uniq_promo_code_redemptions_order (order_id), INDEX idx_promo_code_redemptions_promo (promo_code_id), INDEX idx_promo_code_redemptions_user (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE favorites (user_id INT NOT NULL, product_id INT NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX uniq_favorites_user_product (user_id, product_id), INDEX idx_favorites_user (user_id), INDEX idx_favorites_product (product_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product_blog_posts (product_id INT NOT NULL, blog_post_id INT NOT NULL, sort_order INT DEFAULT 0 NOT NULL, UNIQUE INDEX uniq_product_blog_posts_product_post (product_id, blog_post_id), INDEX idx_product_blog_posts_product (product_id), INDEX idx_product_blog_posts_post (blog_post_id), INDEX idx_product_blog_posts_sort_order (sort_order)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE certificates (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, file_path VARCHAR(512) NOT NULL, document_type VARCHAR(50) NOT NULL, product_id INT DEFAULT NULL, description VARCHAR(500) DEFAULT NULL, is_active TINYINT DEFAULT 1 NOT NULL, sort_order INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX idx_certificates_product (product_id), INDEX idx_certificates_active (is_active), INDEX idx_certificates_sort_order (sort_order), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE faq_items (id INT AUTO_INCREMENT NOT NULL, question VARCHAR(500) NOT NULL, answer LONGTEXT NOT NULL, page_scope VARCHAR(50) NOT NULL, page_id VARCHAR(100) DEFAULT NULL, is_active TINYINT DEFAULT 1 NOT NULL, sort_order INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX idx_faq_items_scope (page_scope, page_id), INDEX idx_faq_items_active (is_active), INDEX idx_faq_items_sort_order (sort_order), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('ALTER TABLE products ADD usage_text LONGTEXT DEFAULT NULL, ADD contraindications LONGTEXT DEFAULT NULL, ADD country VARCHAR(100) DEFAULT NULL, ADD shelf_life VARCHAR(255) DEFAULT NULL, ADD storage_conditions VARCHAR(500) DEFAULT NULL, ADD bad_disclaimer VARCHAR(500) DEFAULT NULL, ADD active_components_text LONGTEXT DEFAULT NULL');

        $this->addSql("INSERT INTO pages (page_type, system_key, title, h1, seo_title, seo_description, og_title, og_description, is_published, is_indexable, show_in_sitemap, sort_order, created_at)
            VALUES ('system', 'certificates', 'Сертификаты качества', 'Сертификаты качества', 'Сертификаты качества — БИОФАРМ', 'Сертификаты, декларации и документы качества на продукцию БИОФАРМ.', 'Сертификаты качества — БИОФАРМ', 'Сертификаты, декларации и документы качества на продукцию БИОФАРМ.', 1, 1, 1, 80, UTC_TIMESTAMP())
            ON DUPLICATE KEY UPDATE updated_at = UTC_TIMESTAMP()");
    }

    #[Override]
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE products DROP usage_text, DROP contraindications, DROP country, DROP shelf_life, DROP storage_conditions, DROP bad_disclaimer, DROP active_components_text');
        $this->addSql("DELETE FROM pages WHERE page_type = 'system' AND system_key = 'certificates'");
        $this->addSql('DROP TABLE faq_items');
        $this->addSql('DROP TABLE certificates');
        $this->addSql('DROP TABLE product_blog_posts');
        $this->addSql('DROP TABLE favorites');
        $this->addSql('DROP TABLE promo_code_redemptions');
        $this->addSql('DROP TABLE promo_codes');
        $this->addSql('DROP INDEX idx_orders_promo_code ON orders');
        $this->addSql('ALTER TABLE orders DROP subtotal, DROP delivery_method, DROP delivery_cost, DROP discount_amount, DROP promo_code');
    }
}
