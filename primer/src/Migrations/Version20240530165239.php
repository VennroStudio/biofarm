<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240530165239 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tracks ADD spotify_analysis_meta LONGTEXT DEFAULT NULL, ADD spotify_analysis_track LONGTEXT DEFAULT NULL, ADD spotify_analysis_bars LONGTEXT DEFAULT NULL, ADD spotify_analysis_beats LONGTEXT DEFAULT NULL, ADD spotify_analysis_sections LONGTEXT DEFAULT NULL, ADD spotify_analysis_segments LONGTEXT DEFAULT NULL, ADD spotify_analysis_tatums LONGTEXT DEFAULT NULL, DROP spotify_analysis, DROP spotify_segments');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tracks ADD spotify_analysis LONGTEXT DEFAULT NULL, ADD spotify_segments LONGTEXT DEFAULT NULL, DROP spotify_analysis_meta, DROP spotify_analysis_track, DROP spotify_analysis_bars, DROP spotify_analysis_beats, DROP spotify_analysis_sections, DROP spotify_analysis_segments, DROP spotify_analysis_tatums');
    }
}
