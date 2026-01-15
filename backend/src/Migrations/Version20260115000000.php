<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260115000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create biofarm_admins table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE biofarm_admins (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL,
            passwordHash VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT "admin",
            isActive BOOLEAN NOT NULL DEFAULT TRUE,
            createdAt INT NOT NULL,
            updatedAt INT NULL,
            INDEX IDX_ADMIN_ACTIVE (isActive),
            INDEX IDX_ADMIN_ROLE (role)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        
        // Создаем начального админа: admin@biofarm.ru / admin123
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        $createdAt = time();
        $this->addSql("INSERT INTO biofarm_admins (email, name, passwordHash, role, isActive, createdAt) 
            VALUES ('admin@biofarm.ru', 'Администратор', '{$passwordHash}', 'admin', TRUE, {$createdAt})");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE biofarm_admins');
    }
}
