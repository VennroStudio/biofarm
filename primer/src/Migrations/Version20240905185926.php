<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240905185926 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE possibly_artists ADD spotify_id VARCHAR(500) DEFAULT NULL, ADD apple_id VARCHAR(500) DEFAULT NULL, ADD tidal_id VARCHAR(500) DEFAULT NULL, DROP spotify_url, DROP apple_url, DROP tidal_url');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE possibly_artists ADD spotify_url VARCHAR(500) DEFAULT NULL, ADD apple_url VARCHAR(500) DEFAULT NULL, ADD tidal_url VARCHAR(500) DEFAULT NULL, DROP spotify_id, DROP apple_id, DROP tidal_id');
    }
}
