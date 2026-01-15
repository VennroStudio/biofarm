<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240712184604 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artists ADD spotify_count_socials INT DEFAULT 0 NOT NULL, ADD tidal_count_socials INT DEFAULT 0 NOT NULL, ADD apple_count_socials INT DEFAULT 0 NOT NULL, ADD spotify_count_albums INT DEFAULT 0 NOT NULL, ADD tidal_count_albums INT DEFAULT 0 NOT NULL, ADD apple_count_albums INT DEFAULT 0 NOT NULL, ADD count_approved INT DEFAULT 0 NOT NULL, ADD count_approved_with_tracks INT DEFAULT 0 NOT NULL, ADD count_conflicts INT DEFAULT 0 NOT NULL, ADD count_loaded INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artists DROP spotify_count_socials, DROP tidal_count_socials, DROP apple_count_socials, DROP spotify_count_albums, DROP tidal_count_albums, DROP apple_count_albums, DROP count_approved, DROP count_approved_with_tracks, DROP count_conflicts, DROP count_loaded');
    }
}
