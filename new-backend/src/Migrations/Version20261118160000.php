<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261118160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Register public legal pages in the admin and retain their edited revisions.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE legal_document_revisions (
            slug VARCHAR(100) NOT NULL,
            digest CHAR(64) NOT NULL,
            content LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (slug, digest)
        ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4");

        $documents = [
            'oferta' => 'Публичная оферта',
            'privacy' => 'Политика обработки персональных данных',
            'personal-data-consent' => 'Согласие на обработку персональных данных',
            'vozvrat' => 'Возврат и обмен товаров',
            'user-agreement' => 'Пользовательское соглашение',
            'marketing-consent' => 'Согласие на получение рекламы',
            'cookie-policy' => 'Политика использования файлов cookie',
        ];

        foreach ($documents as $slug => $title) {
            $this->addSql(
                "INSERT INTO pages (page_type, system_key, title, is_published, is_indexable, show_in_sitemap, show_in_footer, sort_order, created_at)
                 SELECT 'system', ?, ?, 1, COALESCE(old.is_indexable, 1), COALESCE(old.show_in_sitemap, 1), COALESCE(old.show_in_footer, 0), COALESCE(old.sort_order, 100), UTC_TIMESTAMP()
                 FROM (SELECT 1 AS seed) s LEFT JOIN pages old ON old.slug_path = ? AND old.deleted_at IS NULL
                 ON DUPLICATE KEY UPDATE system_key = VALUES(system_key)",
                [$slug, $title, $slug],
            );
            // Preserve the legacy editor's data without publishing its stale document.
            $this->addSql(
                "UPDATE pages SET deleted_at = UTC_TIMESTAMP(), is_published = 0
                 WHERE page_type = 'custom' AND slug_path = ? AND deleted_at IS NULL",
                [$slug],
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Legal document revisions and previous CMS content must be retained.');
    }
}
