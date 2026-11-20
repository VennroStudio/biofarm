<?php

declare(strict_types=1);
namespace App\Migrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20261011121000 extends AbstractMigration
{
    public function getDescription(): string { return 'Separate product legal status, official purpose and internal evidence.'; }
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE products ADD legal_status VARCHAR(20) NOT NULL DEFAULT 'unknown', ADD official_purpose LONGTEXT DEFAULT NULL, ADD regulatory_evidence LONGTEXT DEFAULT NULL");
    }
    public function down(Schema $schema): void { $this->throwIrreversibleMigrationException('Product evidence must be preserved.'); }
}
