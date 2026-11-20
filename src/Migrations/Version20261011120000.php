<?php

declare(strict_types=1);
namespace App\Migrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20261011120000 extends AbstractMigration
{
    public function getDescription(): string { return 'Separate versioned evidence of personal-data, marketing and cookie choices.'; }
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE privacy_consents (id VARCHAR(32) PRIMARY KEY, subject_type VARCHAR(20) NOT NULL, subject_id VARCHAR(100) NOT NULL, kind VARCHAR(30) NOT NULL, version VARCHAR(20) NOT NULL, accepted BOOLEAN NOT NULL, created_at DATETIME NOT NULL, evidence JSON NOT NULL, INDEX idx_consent_subject (subject_type,subject_id,kind,created_at)) DEFAULT CHARACTER SET utf8mb4');
    }
    public function down(Schema $schema): void { $this->throwIrreversibleMigrationException('Consent evidence must be retained; restore a reviewed backup instead.'); }
}
