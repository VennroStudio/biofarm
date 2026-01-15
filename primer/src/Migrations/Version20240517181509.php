<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240517181509 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tidal_albums CHANGE barcode_id barcode_id VARCHAR(50) NOT NULL, CHANGE artists artists LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE tidal_tracks CHANGE isrc isrc VARCHAR(20) NOT NULL, CHANGE artists artists LONGTEXT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tidal_albums CHANGE artists artists LONGTEXT DEFAULT NULL, CHANGE barcode_id barcode_id VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE tidal_tracks CHANGE isrc isrc VARCHAR(20) DEFAULT NULL, CHANGE artists artists LONGTEXT DEFAULT NULL');
    }
}
