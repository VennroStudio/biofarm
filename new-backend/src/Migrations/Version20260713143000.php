<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Override;

final class Version20260713143000 extends AbstractMigration
{
    #[Override]
    public function getDescription(): string
    {
        return 'Add product image metadata and seed default product purposes.';
    }

    #[Override]
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product_images ADD mime_type VARCHAR(100) DEFAULT NULL, ADD size INT DEFAULT NULL');

        $this->addSql(
            "INSERT INTO product_purposes (slug, name, h1, seo_title, seo_description, is_indexable, sort_order, created_at) VALUES
                ('dlya-pecheni', 'для печени', 'БАДы для печени', 'БАДы для печени — БИОФАРМ', 'Натуральная продукция БИОФАРМ для поддержки печени.', 1, 10, UTC_TIMESTAMP()),
                ('dlya-immuniteta', 'для иммунитета', 'БАДы для иммунитета', 'БАДы для иммунитета — БИОФАРМ', 'Натуральная продукция БИОФАРМ для поддержки иммунитета.', 1, 20, UTC_TIMESTAMP()),
                ('dlya-mozga', 'для мозга', 'БАДы для мозга', 'БАДы для мозга — БИОФАРМ', 'Натуральная продукция БИОФАРМ для поддержки работы мозга.', 1, 30, UTC_TIMESTAMP()),
                ('dlya-pishchevareniya', 'для пищеварения', 'БАДы для пищеварения', 'БАДы для пищеварения — БИОФАРМ', 'Натуральная продукция БИОФАРМ для поддержки пищеварения.', 1, 40, UTC_TIMESTAMP()),
                ('dlya-serdtsa-i-sosudov', 'для сердца и сосудов', 'БАДы для сердца и сосудов', 'БАДы для сердца и сосудов — БИОФАРМ', 'Натуральная продукция БИОФАРМ для поддержки сердца и сосудов.', 1, 50, UTC_TIMESTAMP()),
                ('dlya-energii', 'для энергии', 'БАДы для энергии', 'БАДы для энергии — БИОФАРМ', 'Натуральная продукция БИОФАРМ для поддержки энергии.', 1, 60, UTC_TIMESTAMP()),
                ('dlya-muzhskogo-zdorovya', 'для мужского здоровья', 'БАДы для мужского здоровья', 'БАДы для мужского здоровья — БИОФАРМ', 'Натуральная продукция БИОФАРМ для поддержки мужского здоровья.', 1, 70, UTC_TIMESTAMP())
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                h1 = VALUES(h1),
                seo_title = VALUES(seo_title),
                seo_description = VALUES(seo_description),
                deleted_at = NULL"
        );
    }

    #[Override]
    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM product_purposes WHERE slug IN ('dlya-pecheni', 'dlya-immuniteta', 'dlya-mozga', 'dlya-pishchevareniya', 'dlya-serdtsa-i-sosudov', 'dlya-energii', 'dlya-muzhskogo-zdorovya')");
        $this->addSql('ALTER TABLE product_images DROP mime_type, DROP size');
    }
}
