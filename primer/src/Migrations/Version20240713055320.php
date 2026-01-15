<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240713055320 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE artist_stats (id BIGINT AUTO_INCREMENT NOT NULL, artist_id INT NOT NULL, spotify_count_socials INT DEFAULT 0 NOT NULL, tidal_count_socials INT DEFAULT 0 NOT NULL, apple_count_socials INT DEFAULT 0 NOT NULL, spotify_count_albums INT DEFAULT 0 NOT NULL, tidal_count_albums INT DEFAULT 0 NOT NULL, apple_count_albums INT DEFAULT 0 NOT NULL, count_approved INT DEFAULT 0 NOT NULL, count_approved_with_tracks INT DEFAULT 0 NOT NULL, count_conflicts INT DEFAULT 0 NOT NULL, count_loaded INT DEFAULT 0 NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('INSERT INTO artist_stats (artist_id, spotify_count_socials, tidal_count_socials, apple_count_socials, spotify_count_albums, tidal_count_albums, apple_count_albums, count_approved, count_approved_with_tracks, count_conflicts, count_loaded) SELECT id, spotify_count_socials, tidal_count_socials, apple_count_socials, spotify_count_albums, tidal_count_albums, apple_count_albums, count_approved, count_approved_with_tracks, count_conflicts, count_loaded FROM artists');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE artist_stats');
    }
}
