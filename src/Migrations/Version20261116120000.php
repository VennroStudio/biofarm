<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261116120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store checkout phone challenges and verified numbers with Ozon delivery checks.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD email_confirmation_required TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE user_profiles
            ADD verified_phone VARCHAR(11) DEFAULT NULL,
            ADD phone_verified_at DATETIME DEFAULT NULL,
            ADD phone_verification_sandbox TINYINT(1) DEFAULT 0 NOT NULL,
            ADD ozon_phone_checked_at DATETIME DEFAULT NULL,
            ADD ozon_phone_available TINYINT(1) DEFAULT NULL,
            ADD UNIQUE INDEX uniq_user_profiles_verified_phone (verified_phone)');
        $this->addSql('CREATE TABLE phone_verification_challenges (
            id CHAR(64) NOT NULL PRIMARY KEY,
            phone VARCHAR(11) NOT NULL,
            user_id INT DEFAULT NULL,
            origin_hash CHAR(64) NOT NULL,
            sandbox TINYINT(1) NOT NULL,
            provider_id BIGINT UNSIGNED DEFAULT NULL,
            state SMALLINT NOT NULL DEFAULT 0,
            attempts SMALLINT NOT NULL DEFAULT 0,
            checked_at BIGINT NOT NULL DEFAULT 0,
            created_at BIGINT NOT NULL,
            expires_at BIGINT NOT NULL,
            consumed_at BIGINT DEFAULT NULL,
            INDEX idx_phone_challenge_phone (phone, sandbox, created_at),
            INDEX idx_phone_challenge_origin (origin_hash, sandbox, created_at),
            INDEX idx_phone_challenge_expiry (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Phone login and email confirmation state must be retained.');
    }
}
