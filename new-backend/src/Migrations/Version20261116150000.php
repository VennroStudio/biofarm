<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261116150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Track production Ozon pickup orders and cache pickup addresses.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE ozon_delivery_orders ADD mode VARCHAR(10) DEFAULT 'sandbox' NOT NULL,
            ADD next_check_at DATETIME DEFAULT NULL, ADD fiscal_items JSON DEFAULT NULL,
            ADD UNIQUE INDEX uniq_ozon_delivery_number (mode, external_number),
            ADD INDEX idx_ozon_delivery_work (mode, status, next_check_at)");
        $this->addSql("CREATE TABLE ozon_pickup_points (
            id BIGINT NOT NULL PRIMARY KEY,
            address VARCHAR(500) NOT NULL,
            city VARCHAR(255) NOT NULL,
            refreshed_at DATETIME NOT NULL,
            INDEX idx_ozon_pickup_city (city, id)
        ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Production Ozon orders and fiscal data must be retained.');
    }
}
