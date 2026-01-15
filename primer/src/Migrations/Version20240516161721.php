<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240516161721 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artists ADD apple_checked_at INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tidal_albums ADD type VARCHAR(20) NOT NULL, ADD videos LONGTEXT DEFAULT NULL, DROP is_album, DROP photo, DROP photo_animated, DROP cover, DROP cover_animated, DROP description, DROP label, DROP attributes, DROP updated_tracks_at');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tidal_albums ADD is_album TINYINT(1) NOT NULL, ADD photo VARCHAR(500) DEFAULT NULL, ADD photo_animated VARCHAR(500) DEFAULT NULL, ADD cover VARCHAR(500) DEFAULT NULL, ADD cover_animated VARCHAR(500) DEFAULT NULL, ADD label VARCHAR(500) DEFAULT NULL, ADD attributes LONGTEXT DEFAULT NULL, ADD updated_tracks_at INT DEFAULT 0 NOT NULL, DROP type, CHANGE videos description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE artists DROP apple_checked_at');
    }
}
