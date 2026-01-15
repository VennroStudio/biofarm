<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240526101526 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE albums ADD apple_album_id INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQUE_APPLE_ALBUM ON albums (apple_album_id)');
        $this->addSql('ALTER TABLE tracks ADD apple_track_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_APPLE_ID ON tracks (apple_track_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_APPLE_ID ON tracks');
        $this->addSql('ALTER TABLE tracks DROP apple_track_id');
        $this->addSql('DROP INDEX UNIQUE_APPLE_ALBUM ON albums');
        $this->addSql('ALTER TABLE albums DROP apple_album_id');
    }
}
