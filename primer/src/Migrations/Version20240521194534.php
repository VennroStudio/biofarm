<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240521194534 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE apple_albums (id BIGINT AUTO_INCREMENT NOT NULL, artist_id INT NOT NULL, apple_id VARCHAR(255) NOT NULL, upc VARCHAR(50) NOT NULL, name VARCHAR(500) NOT NULL, is_compilation TINYINT(1) NOT NULL, is_single TINYINT(1) NOT NULL, released_at INT DEFAULT NULL, total_tracks INT NOT NULL, artists LONGTEXT NOT NULL, images LONGTEXT DEFAULT NULL, videos LONGTEXT DEFAULT NULL, copyrights LONGTEXT DEFAULT NULL, label LONGTEXT DEFAULT NULL, genre_names LONGTEXT DEFAULT NULL, attributes LONGTEXT DEFAULT NULL, updated_at INT DEFAULT 0 NOT NULL, is_deleted TINYINT(1) NOT NULL, INDEX IDX_ARTIST_ID (artist_id), UNIQUE INDEX UNIQUE_APPLE_ALBUM (artist_id, apple_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE apple_tracks (id BIGINT AUTO_INCREMENT NOT NULL, apple_album_id INT NOT NULL, apple_id BIGINT NOT NULL, disk_number INT NOT NULL, track_number INT NOT NULL, isrc VARCHAR(20) NOT NULL, name VARCHAR(500) NOT NULL, artists LONGTEXT NOT NULL, composers LONGTEXT DEFAULT NULL, duration INT NOT NULL, genre_names LONGTEXT DEFAULT NULL, attributes LONGTEXT DEFAULT NULL, is_deleted TINYINT(1) NOT NULL, UNIQUE INDEX UNIQUE_APPLE_TRACK (apple_album_id, apple_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE apple_albums');
        $this->addSql('DROP TABLE apple_tracks');
    }
}
