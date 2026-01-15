<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add referredBy field to orders table
 */
final class Version20260114130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add referredBy field to biofarm_orders table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE biofarm_orders ADD referredBy VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE biofarm_orders DROP COLUMN referredBy');
    }
}
