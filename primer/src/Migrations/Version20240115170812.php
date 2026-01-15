<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240115170812 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE tidal_albums (id BIGINT AUTO_INCREMENT NOT NULL, artist_id INT NOT NULL, tidal_id INT NOT NULL, is_album TINYINT(1) NOT NULL, name VARCHAR(500) NOT NULL, photo VARCHAR(500) DEFAULT NULL, photo_animated VARCHAR(500) DEFAULT NULL, cover VARCHAR(500) DEFAULT NULL, cover_animated VARCHAR(500) DEFAULT NULL, description LONGTEXT DEFAULT NULL, artists LONGTEXT DEFAULT NULL, released_at INT DEFAULT NULL, total_tracks INT NOT NULL, label VARCHAR(500) DEFAULT NULL, attributes LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQUE_TIDAL_ALBUM (artist_id, tidal_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('DROP INDEX UNIQUE_TIDAL_ALBUM ON albums');
        $this->addSql('ALTER TABLE albums ADD tidal_album_id INT DEFAULT NULL, DROP tidal_id, DROP tidal_is_album, DROP tidal_name, DROP tidal_photo, DROP tidal_photo_animated, DROP tidal_cover, DROP tidal_cover_animated, DROP tidal_description, DROP tidal_artists, DROP tidal_released_at, DROP tidal_total_tracks, DROP tidal_label, DROP tidal_attributes');
        $this->addSql('CREATE UNIQUE INDEX UNIQUE_TIDAL_ALBUM ON albums (artist_id, tidal_album_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE tidal_albums');
        $this->addSql('DROP INDEX UNIQUE_TIDAL_ALBUM ON albums');
        $this->addSql('ALTER TABLE albums ADD tidal_is_album TINYINT(1) DEFAULT NULL, ADD tidal_name VARCHAR(500) DEFAULT NULL, ADD tidal_photo VARCHAR(500) DEFAULT NULL, ADD tidal_photo_animated VARCHAR(500) DEFAULT NULL, ADD tidal_cover VARCHAR(500) DEFAULT NULL, ADD tidal_cover_animated VARCHAR(500) DEFAULT NULL, ADD tidal_description LONGTEXT DEFAULT NULL, ADD tidal_artists LONGTEXT DEFAULT NULL, ADD tidal_released_at INT DEFAULT NULL, ADD tidal_total_tracks INT DEFAULT NULL, ADD tidal_label VARCHAR(500) DEFAULT NULL, ADD tidal_attributes LONGTEXT DEFAULT NULL, CHANGE tidal_album_id tidal_id INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQUE_TIDAL_ALBUM ON albums (artist_id, tidal_id)');
    }
}
