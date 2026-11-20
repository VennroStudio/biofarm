<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261112130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store the Ozon catalog links, synchronization state and stock notification queue.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ozon_catalog_products (
            ozon_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
            product_id INT NOT NULL UNIQUE,
            sku BIGINT UNSIGNED DEFAULT NULL,
            fbo_sku BIGINT UNSIGNED DEFAULT NULL,
            stock INT UNSIGNED NOT NULL,
            archived TINYINT NOT NULL,
            KEY idx_ozon_sku (sku), KEY idx_ozon_fbo_sku (fbo_sku)
        ) ENGINE=InnoDB');
        $this->addSql('CREATE TABLE ozon_catalog_groups (
            ozon_model_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
            group_id INT NOT NULL UNIQUE
        ) ENGINE=InnoDB');
        $this->addSql('CREATE TABLE ozon_catalog_state (
            id INT NOT NULL PRIMARY KEY,
            oauth_state CHAR(64) DEFAULT NULL,
            oauth_expires_at DATETIME DEFAULT NULL,
            requested TINYINT NOT NULL DEFAULT 0,
            started_at DATETIME DEFAULT NULL,
            last_success_at DATETIME DEFAULT NULL,
            daily_date DATE DEFAULT NULL,
            retry_after DATETIME DEFAULT NULL,
            cache_pending TINYINT NOT NULL DEFAULT 0,
            result TEXT DEFAULT NULL,
            error VARCHAR(500) DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('INSERT INTO ozon_catalog_state (id) VALUES (1)');
        $this->addSql('CREATE TABLE ozon_stock_queue (
            selector VARCHAR(32) NOT NULL PRIMARY KEY,
            revision BIGINT UNSIGNED NOT NULL DEFAULT 1,
            attempts INT UNSIGNED NOT NULL DEFAULT 0,
            ready_at DATETIME NOT NULL
        ) ENGINE=InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Dropping the Ozon links would lose the synchronization history.');
    }
}
