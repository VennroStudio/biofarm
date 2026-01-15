<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240617160826 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE tracks_additional (id BIGINT AUTO_INCREMENT NOT NULL, track_id INT NOT NULL, spotify_features LONGTEXT DEFAULT NULL, spotify_analysis_meta LONGTEXT DEFAULT NULL, spotify_analysis_track LONGTEXT DEFAULT NULL, spotify_analysis_bars LONGTEXT DEFAULT NULL, spotify_analysis_beats LONGTEXT DEFAULT NULL, spotify_analysis_sections LONGTEXT DEFAULT NULL, spotify_analysis_segments JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', spotify_analysis_tatums LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQUE_TRACK (track_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        // INSERT INTO tracks_additional (track_id, spotify_features, spotify_analysis_meta, spotify_analysis_track, spotify_analysis_bars, spotify_analysis_beats, spotify_analysis_sections, spotify_analysis_segments, spotify_analysis_tatums) SELECT id, spotify_features, spotify_analysis_meta, spotify_analysis_track, spotify_analysis_bars, spotify_analysis_beats, spotify_analysis_sections, spotify_analysis_segments, spotify_analysis_tatums FROM tracks WHERE spotify_additional_checked_at IS NOT NULL;
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE tracks_additional');
    }
}
