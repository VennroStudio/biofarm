<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240825075004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE playlists (id BIGINT AUTO_INCREMENT NOT NULL, union_id INT NOT NULL, user_id INT NOT NULL, priority INT DEFAULT 0 NOT NULL, country_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, type INT NOT NULL, url VARCHAR(500) NOT NULL, is_followed TINYINT(1) NOT NULL, created_at INT NOT NULL, updated_at INT DEFAULT NULL, deleted_at INT DEFAULT NULL, UNIQUE INDEX UNIQUE_URL (url), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE possibly_artists (id BIGINT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, artist_id INT DEFAULT NULL, playlist_id INT DEFAULT NULL, spotify_url VARCHAR(500) DEFAULT NULL, apple_url VARCHAR(500) DEFAULT NULL, tidal_url VARCHAR(500) DEFAULT NULL, created_at INT NOT NULL, updated_at INT DEFAULT NULL, deleted_at INT DEFAULT NULL, UNIQUE INDEX UNIQUE_ARTIST (artist_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('DROP TABLE spotify_playlists');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE spotify_playlists (id BIGINT AUTO_INCREMENT NOT NULL, country_id INT DEFAULT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, url VARCHAR(500) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at INT NOT NULL, updated_at INT DEFAULT NULL, deleted_at INT DEFAULT NULL, UNIQUE INDEX UNIQUE_URL (url), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP TABLE playlists');
        $this->addSql('DROP TABLE possibly_artists');
    }
}
