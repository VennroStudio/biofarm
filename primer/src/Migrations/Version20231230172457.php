<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231230172457 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE albums (id BIGINT AUTO_INCREMENT NOT NULL, artist_id INT NOT NULL, tidal_id INT DEFAULT NULL, tidal_is_album TINYINT(1) DEFAULT NULL, tidal_name VARCHAR(500) DEFAULT NULL, tidal_photo VARCHAR(500) DEFAULT NULL, tidal_photo_animated VARCHAR(500) DEFAULT NULL, tidal_cover VARCHAR(500) DEFAULT NULL, tidal_cover_animated VARCHAR(500) DEFAULT NULL, tidal_description LONGTEXT DEFAULT NULL, tidal_artists LONGTEXT DEFAULT NULL, tidal_released_at INT DEFAULT NULL, tidal_total_tracks INT DEFAULT NULL, tidal_label VARCHAR(500) DEFAULT NULL, tidal_attributes LONGTEXT DEFAULT NULL, spotify_id VARCHAR(255) DEFAULT NULL, spotify_type VARCHAR(20) DEFAULT NULL, spotify_name VARCHAR(255) DEFAULT NULL, spotify_released_at INT DEFAULT NULL, spotify_total_tracks INT DEFAULT NULL, spotify_available_markets LONGTEXT DEFAULT NULL, spotify_artists LONGTEXT DEFAULT NULL, spotify_images LONGTEXT DEFAULT NULL, spotify_copyrights LONGTEXT DEFAULT NULL, spotify_genres LONGTEXT DEFAULT NULL, spotify_label VARCHAR(255) DEFAULT NULL, spotify_popularity INT DEFAULT NULL, UNIQUE INDEX UNIQUE_TIDAL_ALBUM (artist_id, tidal_id), UNIQUE INDEX UNIQUE_SPOTIFY_ALBUM (artist_id, spotify_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE artists (id BIGINT AUTO_INCREMENT NOT NULL, union_id INT NOT NULL, description VARCHAR(255) NOT NULL, yandex VARCHAR(500) DEFAULT NULL, tidal VARCHAR(500) DEFAULT NULL, spotify VARCHAR(500) DEFAULT NULL, apple VARCHAR(500) DEFAULT NULL, youtube VARCHAR(500) DEFAULT NULL, vk VARCHAR(500) DEFAULT NULL, priority INT DEFAULT 0 NOT NULL, checked_at INT DEFAULT NULL, next_check INT DEFAULT 0 NOT NULL, reserved_at INT DEFAULT NULL, created_at INT NOT NULL, updated_at INT DEFAULT NULL, deleted_at INT DEFAULT NULL, UNIQUE INDEX UNIQUE_UNION (union_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE isrc (id BIGINT AUTO_INCREMENT NOT NULL, isrc_id VARCHAR(50) NOT NULL, duration INT NOT NULL, recording_version VARCHAR(500) DEFAULT NULL, recording_type VARCHAR(500) DEFAULT NULL, recording_year INT DEFAULT NULL, recording_artist_name VARCHAR(500) DEFAULT NULL, is_explicit TINYINT(1) DEFAULT NULL, release_label VARCHAR(500) DEFAULT NULL, icpn VARCHAR(500) DEFAULT NULL, release_date INT DEFAULT NULL, genre LONGTEXT DEFAULT NULL, release_name VARCHAR(500) DEFAULT NULL, release_artist_name VARCHAR(500) DEFAULT NULL, recording_title VARCHAR(500) DEFAULT NULL, UNIQUE INDEX UNIQUE_ISRC_ID (isrc_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tracks (id BIGINT AUTO_INCREMENT NOT NULL, album_id INT NOT NULL, tidal_id BIGINT DEFAULT NULL, tidal_disk_number INT DEFAULT NULL, tidal_track_number INT DEFAULT NULL, tidal_name VARCHAR(500) DEFAULT NULL, tidal_explicit TINYINT(1) DEFAULT NULL, tidal_artists LONGTEXT DEFAULT NULL, tidal_attributes LONGTEXT DEFAULT NULL, spotify_id VARCHAR(255) DEFAULT NULL, spotify_disk_number INT DEFAULT NULL, spotify_track_number INT DEFAULT NULL, spotify_name VARCHAR(500) DEFAULT NULL, spotify_explicit TINYINT(1) DEFAULT NULL, spotify_artists LONGTEXT DEFAULT NULL, spotify_isrc VARCHAR(20) DEFAULT NULL, spotify_upc VARCHAR(20) DEFAULT NULL, spotify_ean VARCHAR(20) DEFAULT NULL, spotify_duration INT DEFAULT NULL, spotify_type VARCHAR(20) DEFAULT NULL, spotify_is_local TINYINT(1) DEFAULT NULL, spotify_available_markets LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQUE_TIDAL_TRACK (album_id, tidal_id), UNIQUE INDEX UNIQUE_SPOTIFY_TRACK (album_id, spotify_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE albums');
        $this->addSql('DROP TABLE artists');
        $this->addSql('DROP TABLE isrc');
        $this->addSql('DROP TABLE tracks');
    }
}
