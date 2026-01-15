<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260115000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make shortDescription nullable in biofarm_products table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE biofarm_products MODIFY shortDescription VARCHAR(500) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE biofarm_products MODIFY shortDescription VARCHAR(500) NOT NULL');
    }
}
