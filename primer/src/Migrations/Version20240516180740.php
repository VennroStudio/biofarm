<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240516180740 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE albums ADD spotify_upc VARCHAR(50) DEFAULT NULL, DROP updated_tracks_at, DROP updated_tidal_tracks_at');
        $this->addSql('ALTER TABLE tidal_albums CHANGE barcode_id barcode_id VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tidal_albums CHANGE barcode_id barcode_id LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE albums ADD updated_tracks_at INT DEFAULT 0 NOT NULL, ADD updated_tidal_tracks_at INT DEFAULT 0 NOT NULL, DROP spotify_upc');
    }
}
