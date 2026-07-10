<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260710071517 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE blog_posts (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, excerpt VARCHAR(500) NOT NULL, content LONGTEXT NOT NULL, image VARCHAR(500) NOT NULL, category_id VARCHAR(50) NOT NULL, author_name VARCHAR(255) NOT NULL, read_time INT NOT NULL, is_published TINYINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, INDEX idx_blog_posts_category_id (category_id), INDEX idx_blog_posts_is_published (is_published), UNIQUE INDEX uniq_blog_posts_slug (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE categories (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX uniq_categories_slug (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE order_items (id INT AUTO_INCREMENT NOT NULL, order_id VARCHAR(50) NOT NULL, product_id INT NOT NULL, product_name VARCHAR(255) NOT NULL, price INT NOT NULL, quantity INT NOT NULL, INDEX idx_order_items_order_id (order_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE orders (id VARCHAR(50) NOT NULL, user_id INT NOT NULL, status VARCHAR(20) NOT NULL, payment_status VARCHAR(20) NOT NULL, total INT NOT NULL, bonus_used INT DEFAULT 0 NOT NULL, bonus_earned INT DEFAULT 0 NOT NULL, shipping_address JSON NOT NULL, payment_method VARCHAR(20) NOT NULL, tracking_number VARCHAR(100) DEFAULT NULL, referred_by VARCHAR(50) DEFAULT NULL, created_at DATETIME NOT NULL, paid_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX idx_orders_user_id (user_id), INDEX idx_orders_status (status), INDEX idx_orders_payment_status (payment_status), INDEX idx_orders_referred_by (referred_by), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE products (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, category_id VARCHAR(50) NOT NULL, price INT NOT NULL, old_price INT DEFAULT NULL, image VARCHAR(500) NOT NULL, images JSON DEFAULT NULL, badge VARCHAR(100) DEFAULT NULL, weight VARCHAR(50) NOT NULL, description LONGTEXT NOT NULL, short_description VARCHAR(500) DEFAULT NULL, ingredients LONGTEXT DEFAULT NULL, features JSON DEFAULT NULL, wb_link VARCHAR(500) DEFAULT NULL, ozon_link VARCHAR(500) DEFAULT NULL, is_active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, INDEX idx_products_category_id (category_id), INDEX idx_products_is_active (is_active), UNIQUE INDEX uniq_products_slug (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reviews (id VARCHAR(50) NOT NULL, product_id INT NOT NULL, user_id VARCHAR(50) DEFAULT NULL, user_name VARCHAR(255) NOT NULL, rating INT NOT NULL, text LONGTEXT NOT NULL, images JSON DEFAULT NULL, source VARCHAR(50) NOT NULL, is_approved TINYINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, INDEX idx_reviews_product_id (product_id), INDEX idx_reviews_is_approved (is_approved), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_tokens (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, type INT NOT NULL, token_hash VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL, used_at DATETIME DEFAULT NULL, revoked_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, INDEX idx_user_tokens_user_type (user_id, type), INDEX idx_user_tokens_expires_at (expires_at), UNIQUE INDEX uniq_user_tokens_token_hash (token_hash), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, role INT NOT NULL, avatar VARCHAR(512) DEFAULT NULL, last_name VARCHAR(60) NOT NULL, first_name VARCHAR(60) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, status INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE blog_posts');
        $this->addSql('DROP TABLE categories');
        $this->addSql('DROP TABLE order_items');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP TABLE products');
        $this->addSql('DROP TABLE reviews');
        $this->addSql('DROP TABLE user_tokens');
        $this->addSql('DROP TABLE users');
    }
}
