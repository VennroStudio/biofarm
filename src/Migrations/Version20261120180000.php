<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261120180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Track FBS warehouse stock, reconciliation and checkout reservations.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ozon_catalog_products ADD fbs_stock INT UNSIGNED NOT NULL DEFAULT 0,
            ADD fbs_sku BIGINT UNSIGNED DEFAULT NULL, ADD fbs_warehouses JSON DEFAULT NULL, ADD stock_checked_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE ozon_catalog_state ADD stock_reconciled_at DATETIME DEFAULT NULL, ADD stock_error VARCHAR(500) DEFAULT NULL, ADD points_refreshed_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE TABLE ozon_stock_reservations (
            order_id VARCHAR(64) NOT NULL, product_id INT UNSIGNED NOT NULL, schema_name CHAR(3) NOT NULL,
            quantity INT UNSIGNED NOT NULL, expires_at DATETIME NOT NULL,
            PRIMARY KEY(order_id, product_id, schema_name), INDEX(product_id, expires_at)
        ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Stock history and reservations are in use.');
    }
}
