<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240805180622 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_COUNTER ON albums');
        $this->addSql('CREATE INDEX IDX_COUNTER ON albums (all_tracks_mapped, is_approved, is_reissued, lo_album_id)');
        $this->addSql('DROP INDEX IDX_COUNTER ON tracks');
        $this->addSql('CREATE INDEX IDX_COUNTER ON tracks (is_approved, is_reissued, lo_track_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_COUNTER ON tracks');
        $this->addSql('CREATE INDEX IDX_COUNTER ON tracks (is_approved, lo_track_id)');
        $this->addSql('DROP INDEX IDX_COUNTER ON albums');
        $this->addSql('CREATE INDEX IDX_COUNTER ON albums (all_tracks_mapped, is_approved, lo_album_id)');
    }
}
