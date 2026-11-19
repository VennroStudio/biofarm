<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261113120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Preserve kopecks in order totals and delivery costs.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders MODIFY total DECIMAL(12,2) NOT NULL, MODIFY delivery_cost DECIMAL(12,2) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Converting order amounts to whole rubles can change recorded payments.');
    }
}
