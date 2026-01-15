<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240512134252 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artist_socials ADD avatar VARCHAR(500) DEFAULT NULL, ADD name VARCHAR(255) DEFAULT NULL, ADD info LONGTEXT DEFAULT NULL, DROP checked_at, DROP unofficial_checked_at, DROP next_check, DROP reserved_at');
        $this->addSql('ALTER TABLE artists ADD avatar VARCHAR(500) DEFAULT NULL, DROP next_check, DROP reserved_at');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artists ADD next_check INT DEFAULT 0 NOT NULL, ADD reserved_at INT DEFAULT NULL, DROP avatar');
        $this->addSql('ALTER TABLE artist_socials ADD checked_at INT DEFAULT NULL, ADD unofficial_checked_at INT DEFAULT NULL, ADD next_check INT DEFAULT 0 NOT NULL, ADD reserved_at INT DEFAULT NULL, DROP avatar, DROP name, DROP info');
    }
}
