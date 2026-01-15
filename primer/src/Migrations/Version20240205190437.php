<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240205190437 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE artist_socials (id BIGINT AUTO_INCREMENT NOT NULL, artist_id INT NOT NULL, type INT NOT NULL, url VARCHAR(500) NOT NULL, description VARCHAR(255) NOT NULL, checked_at INT DEFAULT NULL, unofficial_checked_at INT DEFAULT NULL, next_check INT DEFAULT 0 NOT NULL, reserved_at INT DEFAULT NULL, created_at INT NOT NULL, updated_at INT DEFAULT NULL, deleted_at INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE artist_socials');
    }
}
