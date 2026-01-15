<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240211101257 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artists DROP yandex, DROP tidal, DROP spotify, DROP apple, DROP youtube, DROP vk, DROP spotify_checked_at, DROP tidal_checked_at, DROP tidal_unofficial_checked_at');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artists ADD yandex VARCHAR(500) DEFAULT NULL, ADD tidal VARCHAR(500) DEFAULT NULL, ADD spotify VARCHAR(500) DEFAULT NULL, ADD apple VARCHAR(500) DEFAULT NULL, ADD youtube VARCHAR(500) DEFAULT NULL, ADD vk VARCHAR(500) DEFAULT NULL, ADD spotify_checked_at INT DEFAULT NULL, ADD tidal_checked_at INT DEFAULT NULL, ADD tidal_unofficial_checked_at INT DEFAULT NULL');
    }
}
