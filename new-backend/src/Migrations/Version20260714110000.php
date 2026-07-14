<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Override;

final class Version20260714110000 extends AbstractMigration
{
    #[Override]
    public function getDescription(): string
    {
        return 'Add editable CMS pages and SEO settings for system pages.';
    }

    #[Override]
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE pages (id INT AUTO_INCREMENT NOT NULL, page_type VARCHAR(20) NOT NULL, system_key VARCHAR(100) DEFAULT NULL, slug_path VARCHAR(255) DEFAULT NULL, template VARCHAR(50) DEFAULT NULL, title VARCHAR(255) NOT NULL, h1 VARCHAR(255) DEFAULT NULL, content LONGTEXT DEFAULT NULL, excerpt VARCHAR(500) DEFAULT NULL, seo_title VARCHAR(255) DEFAULT NULL, seo_description VARCHAR(500) DEFAULT NULL, og_title VARCHAR(255) DEFAULT NULL, og_description VARCHAR(500) DEFAULT NULL, og_image VARCHAR(512) DEFAULT NULL, og_image_alt VARCHAR(255) DEFAULT NULL, is_published TINYINT DEFAULT 1 NOT NULL, is_indexable TINYINT DEFAULT 1 NOT NULL, show_in_sitemap TINYINT DEFAULT 1 NOT NULL, show_in_header TINYINT DEFAULT 0 NOT NULL, show_in_footer TINYINT DEFAULT 0 NOT NULL, sort_order INT DEFAULT 0 NOT NULL, published_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX uniq_pages_system_key (system_key), UNIQUE INDEX uniq_pages_slug_path (slug_path), INDEX idx_pages_page_type (page_type), INDEX idx_pages_published (is_published), INDEX idx_pages_sitemap (show_in_sitemap), INDEX idx_pages_sort_order (sort_order), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql(
            "INSERT INTO pages (page_type, system_key, title, h1, seo_title, seo_description, og_title, og_description, is_published, is_indexable, show_in_sitemap, sort_order, created_at)
            VALUES
                ('system', 'home', 'Главная', 'БИОФАРМ', 'БИОФАРМ — натуральные продукты', 'Экологически чистые продукты БИОФАРМ напрямую из собственных лабораторий.', 'БИОФАРМ — натуральные продукты', 'Экологически чистые продукты БИОФАРМ напрямую из собственных лабораторий.', 1, 1, 1, 10, UTC_TIMESTAMP()),
                ('system', 'catalog', 'Каталог', 'Каталог товаров', 'Каталог товаров — БИОФАРМ', 'Натуральные продукты из собственной лаборатории БИОФАРМ.', 'Каталог товаров — БИОФАРМ', 'Натуральные продукты из собственной лаборатории БИОФАРМ.', 1, 1, 1, 20, UTC_TIMESTAMP()),
                ('system', 'blog', 'Блог', 'Блог БИОФАРМ', 'Блог — БИОФАРМ', 'Статьи и новости БИОФАРМ.', 'Блог — БИОФАРМ', 'Статьи и новости БИОФАРМ.', 1, 1, 1, 30, UTC_TIMESTAMP()),
                ('system', 'privacy', 'Политика конфиденциальности', 'Политика конфиденциальности', 'Политика конфиденциальности — БИОФАРМ', 'Политика конфиденциальности БИОФАРМ.', 'Политика конфиденциальности — БИОФАРМ', 'Политика конфиденциальности БИОФАРМ.', 1, 1, 1, 90, UTC_TIMESTAMP()),
                ('system', 'oferta', 'Публичная оферта', 'Публичная оферта', 'Публичная оферта — БИОФАРМ', 'Публичная оферта БИОФАРМ.', 'Публичная оферта — БИОФАРМ', 'Публичная оферта БИОФАРМ.', 1, 1, 1, 100, UTC_TIMESTAMP()),
                ('system', 'cart', 'Корзина', 'Корзина', 'Корзина — БИОФАРМ', 'Корзина товаров БИОФАРМ.', 'Корзина — БИОФАРМ', 'Корзина товаров БИОФАРМ.', 1, 0, 0, 110, UTC_TIMESTAMP()),
                ('system', 'checkout', 'Оформление заказа', 'Оформление заказа', 'Оформление заказа — БИОФАРМ', 'Оформление заказа БИОФАРМ.', 'Оформление заказа — БИОФАРМ', 'Оформление заказа БИОФАРМ.', 1, 0, 0, 120, UTC_TIMESTAMP()),
                ('system', 'order_success', 'Заказ оформлен', 'Заказ оформлен', 'Заказ оформлен — БИОФАРМ', 'Заказ БИОФАРМ успешно оформлен.', 'Заказ оформлен — БИОФАРМ', 'Заказ БИОФАРМ успешно оформлен.', 1, 0, 0, 130, UTC_TIMESTAMP()),
                ('system', 'login', 'Личный кабинет', 'Личный кабинет', 'Личный кабинет — БИОФАРМ', 'Вход в личный кабинет БИОФАРМ.', 'Личный кабинет — БИОФАРМ', 'Вход в личный кабинет БИОФАРМ.', 1, 0, 0, 140, UTC_TIMESTAMP()),
                ('system', 'profile', 'Профиль', 'Профиль', 'Профиль — БИОФАРМ', 'Профиль, заказы и реферальная программа БИОФАРМ.', 'Профиль — БИОФАРМ', 'Профиль, заказы и реферальная программа БИОФАРМ.', 1, 0, 0, 150, UTC_TIMESTAMP())
            ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                h1 = VALUES(h1),
                seo_title = VALUES(seo_title),
                seo_description = VALUES(seo_description),
                og_title = VALUES(og_title),
                og_description = VALUES(og_description),
                is_published = VALUES(is_published),
                is_indexable = VALUES(is_indexable),
                show_in_sitemap = VALUES(show_in_sitemap),
                sort_order = VALUES(sort_order),
                deleted_at = NULL"
        );
    }

    #[Override]
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE pages');
    }
}
