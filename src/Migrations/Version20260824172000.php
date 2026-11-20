<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Override;

final class Version20260824172000 extends AbstractMigration
{
    #[Override]
    public function getDescription(): string
    {
        return 'Add editable full robots.txt site setting.';
    }

    #[Override]
    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('site_settings')) {
            return;
        }

        $this->addSql(
            'INSERT INTO site_settings (`key`, value)
             VALUES (:key, :value)
             ON DUPLICATE KEY UPDATE `key` = `key`',
            [
                'key'   => 'robots_txt',
                'value' => json_encode(['value' => ''], JSON_THROW_ON_ERROR),
            ],
        );
    }

    #[Override]
    public function down(Schema $schema): void
    {
        if ($schema->hasTable('site_settings')) {
            $this->addSql("DELETE FROM site_settings WHERE `key` = 'robots_txt'");
        }
    }
}
