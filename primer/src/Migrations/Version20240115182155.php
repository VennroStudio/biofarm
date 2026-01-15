<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240115182155 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE album_artists (id BIGINT AUTO_INCREMENT NOT NULL, album_id INT NOT NULL, artist_id INT NOT NULL, UNIQUE INDEX `UNIQUE` (album_id, artist_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('DROP INDEX UNIQUE_TIDAL_ALBUM ON albums');
        $this->addSql('DROP INDEX UNIQUE_SPOTIFY_ALBUM ON albums');
        $this->addSql('ALTER TABLE albums DROP artist_id');
        $this->addSql('CREATE UNIQUE INDEX UNIQUE_TIDAL_ALBUM ON albums (tidal_album_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQUE_SPOTIFY_ALBUM ON albums (spotify_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE album_artists');
        $this->addSql('DROP INDEX UNIQUE_TIDAL_ALBUM ON albums');
        $this->addSql('DROP INDEX UNIQUE_SPOTIFY_ALBUM ON albums');
        $this->addSql('ALTER TABLE albums ADD artist_id INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQUE_TIDAL_ALBUM ON albums (artist_id, tidal_album_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQUE_SPOTIFY_ALBUM ON albums (artist_id, spotify_id)');
    }
}
