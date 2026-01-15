<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240504075406 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE album_artists ADD is_loaded TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE albums DROP is_loaded');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE album_artists DROP is_loaded');
        $this->addSql('ALTER TABLE albums ADD is_loaded TINYINT(1) NOT NULL');
    }
}
