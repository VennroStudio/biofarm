<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261112132000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist Ozon delivery dispatches for paid test checkouts.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE ozon_delivery_orders (
            order_id VARCHAR(100) NOT NULL PRIMARY KEY,
            status VARCHAR(20) NOT NULL,
            payload JSON NOT NULL,
            external_number VARCHAR(50) DEFAULT NULL,
            postings JSON DEFAULT NULL,
            error VARCHAR(500) DEFAULT NULL,
            updated_at DATETIME NOT NULL,
            KEY idx_ozon_delivery_status (status, updated_at)
        ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Ozon order references must be retained.');
    }
}
