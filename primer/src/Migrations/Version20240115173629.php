<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240115173629 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE tidal_tracks (id BIGINT AUTO_INCREMENT NOT NULL, tidal_album_id INT NOT NULL, tidal_id BIGINT NOT NULL, disk_number INT NOT NULL, track_number INT NOT NULL, name VARCHAR(500) NOT NULL, explicit TINYINT(1) NOT NULL, artists LONGTEXT DEFAULT NULL, attributes LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQUE_TIDAL_TRACK (tidal_album_id, tidal_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE albums CHANGE spotify_id spotify_id VARCHAR(255) NOT NULL, CHANGE spotify_type spotify_type VARCHAR(20) NOT NULL, CHANGE spotify_name spotify_name VARCHAR(255) NOT NULL, CHANGE spotify_total_tracks spotify_total_tracks INT NOT NULL');
        $this->addSql('DROP INDEX UNIQUE_TIDAL_TRACK ON tracks');
        $this->addSql('ALTER TABLE tracks ADD tidal_track_id INT DEFAULT NULL, DROP tidal_id, DROP tidal_disk_number, DROP tidal_track_number, DROP tidal_name, DROP tidal_explicit, DROP tidal_artists, DROP tidal_attributes, CHANGE spotify_id spotify_id VARCHAR(255) NOT NULL, CHANGE spotify_disk_number spotify_disk_number INT NOT NULL, CHANGE spotify_track_number spotify_track_number INT NOT NULL, CHANGE spotify_name spotify_name VARCHAR(500) NOT NULL, CHANGE spotify_explicit spotify_explicit TINYINT(1) NOT NULL, CHANGE spotify_duration spotify_duration INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQUE_TIDAL_TRACK ON tracks (album_id, tidal_track_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE tidal_tracks');
        $this->addSql('DROP INDEX UNIQUE_TIDAL_TRACK ON tracks');
        $this->addSql('ALTER TABLE tracks ADD tidal_id BIGINT DEFAULT NULL, ADD tidal_track_number INT DEFAULT NULL, ADD tidal_name VARCHAR(500) DEFAULT NULL, ADD tidal_explicit TINYINT(1) DEFAULT NULL, ADD tidal_artists LONGTEXT DEFAULT NULL, ADD tidal_attributes LONGTEXT DEFAULT NULL, CHANGE spotify_id spotify_id VARCHAR(255) DEFAULT NULL, CHANGE spotify_disk_number spotify_disk_number INT DEFAULT NULL, CHANGE spotify_track_number spotify_track_number INT DEFAULT NULL, CHANGE spotify_name spotify_name VARCHAR(500) DEFAULT NULL, CHANGE spotify_explicit spotify_explicit TINYINT(1) DEFAULT NULL, CHANGE spotify_duration spotify_duration INT DEFAULT NULL, CHANGE tidal_track_id tidal_disk_number INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQUE_TIDAL_TRACK ON tracks (album_id, tidal_id)');
        $this->addSql('ALTER TABLE albums CHANGE spotify_id spotify_id VARCHAR(255) DEFAULT NULL, CHANGE spotify_type spotify_type VARCHAR(20) DEFAULT NULL, CHANGE spotify_name spotify_name VARCHAR(255) DEFAULT NULL, CHANGE spotify_total_tracks spotify_total_tracks INT DEFAULT NULL');
    }
}
