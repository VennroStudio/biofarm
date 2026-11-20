<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261116130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Record consent decisions with microsecond precision.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE privacy_consents MODIFY created_at DATETIME(6) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE privacy_consents MODIFY created_at DATETIME NOT NULL');
    }
}
