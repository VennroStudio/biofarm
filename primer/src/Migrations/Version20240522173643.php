<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240522173643 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX IDX_APPLE_ID ON apple_albums (apple_id)');
        $this->addSql('CREATE INDEX IDX_APPLE_ID ON apple_tracks (apple_id)');
        $this->addSql('CREATE INDEX IDX_TIDAL_ID ON tidal_albums (tidal_id)');
        $this->addSql('CREATE INDEX IDX_TIDAL_ID ON tidal_tracks (tidal_id)');
        $this->addSql('CREATE INDEX IDX_TIDAL_ID ON tracks (tidal_track_id)');
        $this->addSql('CREATE INDEX IDX_SPOTIFY_ID ON tracks (spotify_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_APPLE_ID ON apple_tracks');
        $this->addSql('DROP INDEX IDX_TIDAL_ID ON tidal_albums');
        $this->addSql('DROP INDEX IDX_TIDAL_ID ON tracks');
        $this->addSql('DROP INDEX IDX_SPOTIFY_ID ON tracks');
        $this->addSql('DROP INDEX IDX_TIDAL_ID ON tidal_tracks');
        $this->addSql('DROP INDEX IDX_APPLE_ID ON apple_albums');
    }
}
