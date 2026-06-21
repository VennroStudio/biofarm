<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Override;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260323054946 extends AbstractMigration
{
    #[Override]
    public function getDescription(): string
    {
        return '';
    }

    #[Override]
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_tokens (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, type INT NOT NULL, token_hash VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL, used_at DATETIME DEFAULT NULL, revoked_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, INDEX idx_user_tokens_user_type (user_id, type), INDEX idx_user_tokens_expires_at (expires_at), UNIQUE INDEX uniq_user_tokens_token_hash (token_hash), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, role INT NOT NULL, avatar VARCHAR(512) DEFAULT NULL, last_name VARCHAR(60) NOT NULL, first_name VARCHAR(60) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, status INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    #[Override]
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE user_tokens');
        $this->addSql('DROP TABLE users');
    }
}
