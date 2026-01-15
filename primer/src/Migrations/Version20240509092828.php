<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240509092828 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX IDX_ARTIST_ID ON album_artists (artist_id)');
        $this->addSql('CREATE INDEX IDX_ARTIST_ID ON artist_socials (artist_id, type)');
        $this->addSql('CREATE INDEX IDX_MERGED_AT ON artists (merged_at)');
        $this->addSql('CREATE INDEX IDX_PRIORITY ON artists (priority)');
        $this->addSql('CREATE INDEX IDX_ARTIST_ID ON tidal_albums (artist_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_ARTIST_ID ON tidal_albums');
        $this->addSql('DROP INDEX IDX_ARTIST_ID ON album_artists');
        $this->addSql('DROP INDEX IDX_MERGED_AT ON artists');
        $this->addSql('DROP INDEX IDX_PRIORITY ON artists');
        $this->addSql('DROP INDEX IDX_ARTIST_ID ON artist_socials');
    }
}
