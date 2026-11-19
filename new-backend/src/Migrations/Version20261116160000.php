<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261116160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist handed-over Ozon shipments and their fiscal allocations.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE ozon_delivered_postings (
            order_id VARCHAR(100) NOT NULL,
            posting_number VARCHAR(80) NOT NULL,
            items JSON NOT NULL,
            receipt_operation_id CHAR(64) NOT NULL,
            PRIMARY KEY (order_id, posting_number)
        ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Ozon fiscal shipment allocations must be retained.');
    }
}
