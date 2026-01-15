<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create categories table
 */
final class Version20260114120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create categories table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE biofarm_categories (
            id BIGINT AUTO_INCREMENT NOT NULL,
            slug VARCHAR(255) NOT NULL,
            name VARCHAR(255) NOT NULL,
            createdAt INT NOT NULL,
            updatedAt INT DEFAULT NULL,
            UNIQUE INDEX UNIQUE_SLUG (slug),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE biofarm_categories');
    }
}
