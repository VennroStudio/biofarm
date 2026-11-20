<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Override;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260710162718 extends AbstractMigration
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
        $this->addSql('CREATE TABLE bonus_transactions (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, amount INT NOT NULL, type VARCHAR(30) NOT NULL, source_order_id VARCHAR(50) DEFAULT NULL, source_withdrawal_id VARCHAR(50) DEFAULT NULL, comment VARCHAR(500) DEFAULT NULL, created_at DATETIME NOT NULL, INDEX idx_bonus_transactions_user_id (user_id), INDEX idx_bonus_transactions_type (type), INDEX idx_bonus_transactions_source_order_id (source_order_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE media_assets (id INT AUTO_INCREMENT NOT NULL, path VARCHAR(500) NOT NULL, url VARCHAR(500) NOT NULL, mime_type VARCHAR(100) NOT NULL, size INT NOT NULL, width INT DEFAULT NULL, height INT DEFAULT NULL, original_name VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE site_settings (`key` VARCHAR(100) NOT NULL, value JSON NOT NULL, PRIMARY KEY (`key`)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_profiles (user_id INT NOT NULL, phone VARCHAR(30) DEFAULT NULL, card_number VARCHAR(32) DEFAULT NULL, bonus_balance INT DEFAULT 0 NOT NULL, is_partner TINYINT DEFAULT 0 NOT NULL, referral_code VARCHAR(50) DEFAULT NULL, referred_by_user_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX idx_user_profiles_referred_by_user_id (referred_by_user_id), UNIQUE INDEX uniq_user_profiles_referral_code (referral_code), PRIMARY KEY (user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE withdrawal_requests (id VARCHAR(50) NOT NULL, user_id INT NOT NULL, amount INT NOT NULL, status VARCHAR(20) NOT NULL, processed_by VARCHAR(255) DEFAULT NULL, processed_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX idx_withdrawal_requests_user_id (user_id), INDEX idx_withdrawal_requests_status (status), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    #[Override]
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE bonus_transactions');
        $this->addSql('DROP TABLE media_assets');
        $this->addSql('DROP TABLE site_settings');
        $this->addSql('DROP TABLE user_profiles');
        $this->addSql('DROP TABLE withdrawal_requests');
    }
}
