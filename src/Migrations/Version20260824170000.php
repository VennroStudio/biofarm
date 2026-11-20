<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Override;

final class Version20260824170000 extends AbstractMigration
{
    #[Override]
    public function getDescription(): string
    {
        return 'Add Bitrix24 CRM integration settings, feedback requests and integration error logs.';
    }

    #[Override]
    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('integration_credentials')) {
            $this->addSql(
                'CREATE TABLE integration_credentials (
                    `key` VARCHAR(100) NOT NULL,
                    encrypted_value LONGTEXT NOT NULL,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME DEFAULT NULL,
                    PRIMARY KEY(`key`)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
            );
        }

        if (!$schema->hasTable('integration_error_logs')) {
            $this->addSql(
                'CREATE TABLE integration_error_logs (
                    id INT AUTO_INCREMENT NOT NULL,
                    service VARCHAR(50) NOT NULL,
                    scenario VARCHAR(80) NOT NULL,
                    operation VARCHAR(100) NOT NULL,
                    local_entity_type VARCHAR(80) DEFAULT NULL,
                    local_entity_id VARCHAR(100) DEFAULT NULL,
                    message VARCHAR(1000) NOT NULL,
                    http_status INT DEFAULT NULL,
                    response_body LONGTEXT DEFAULT NULL,
                    context JSON DEFAULT NULL,
                    is_read TINYINT(1) DEFAULT 0 NOT NULL,
                    created_at DATETIME NOT NULL,
                    read_at DATETIME DEFAULT NULL,
                    INDEX idx_integration_logs_service (service),
                    INDEX idx_integration_logs_scenario (scenario),
                    INDEX idx_integration_logs_read_created (is_read, created_at),
                    INDEX idx_integration_logs_entity (local_entity_type, local_entity_id),
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
            );
        }

        if (!$schema->hasTable('feedback_requests')) {
            $this->addSql(
                'CREATE TABLE feedback_requests (
                    id INT AUTO_INCREMENT NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    phone VARCHAR(50) DEFAULT NULL,
                    email VARCHAR(255) DEFAULT NULL,
                    message LONGTEXT NOT NULL,
                    source_page VARCHAR(500) DEFAULT NULL,
                    ip VARCHAR(45) DEFAULT NULL,
                    user_agent VARCHAR(500) DEFAULT NULL,
                    bitrix_lead_id INT DEFAULT NULL,
                    email_sent_at DATETIME DEFAULT NULL,
                    created_at DATETIME NOT NULL,
                    INDEX idx_feedback_created (created_at),
                    INDEX idx_feedback_email (email),
                    INDEX idx_feedback_phone (phone),
                    INDEX idx_feedback_bitrix_lead (bitrix_lead_id),
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
            );
        }

        if ($schema->hasTable('orders') && !$schema->getTable('orders')->hasColumn('bitrix_deal_id')) {
            $this->addSql('ALTER TABLE orders ADD bitrix_deal_id INT DEFAULT NULL');
            $this->addSql('CREATE INDEX idx_orders_bitrix_deal_id ON orders (bitrix_deal_id)');
        }

        if ($schema->hasTable('site_settings')) {
            $this->addSql(
                'INSERT INTO site_settings (`key`, value)
                 VALUES (:key, :value)
                 ON DUPLICATE KEY UPDATE `key` = `key`',
                [
                    'key'   => 'bitrix_crm_enabled',
                    'value' => '{"value":false}',
                ],
            );
        }
    }

    #[Override]
    public function down(Schema $schema): void
    {
        if ($schema->hasTable('site_settings')) {
            $this->addSql("DELETE FROM site_settings WHERE `key` = 'bitrix_crm_enabled'");
        }

        if ($schema->hasTable('orders') && $schema->getTable('orders')->hasColumn('bitrix_deal_id')) {
            $this->addSql('DROP INDEX idx_orders_bitrix_deal_id ON orders');
            $this->addSql('ALTER TABLE orders DROP bitrix_deal_id');
        }

        if ($schema->hasTable('feedback_requests')) {
            $this->addSql('DROP TABLE feedback_requests');
        }

        if ($schema->hasTable('integration_error_logs')) {
            $this->addSql('DROP TABLE integration_error_logs');
        }

        if ($schema->hasTable('integration_credentials')) {
            $this->addSql('DROP TABLE integration_credentials');
        }
    }
}
