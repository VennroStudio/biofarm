<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Override;

final class Version20260824133000 extends AbstractMigration
{
    #[Override]
    public function getDescription(): string
    {
        return 'Disable duplicate trust pages with legacy English slugs.';
    }

    #[Override]
    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('pages')) {
            return;
        }

        foreach ([
            'delivery' => 'dostavka',
            'payment'  => 'oplata',
            'return'   => 'vozvrat',
        ] as $legacySlug => $currentSlug) {
            $this->addSql(
                'UPDATE pages legacy
                 SET legacy.deleted_at = UTC_TIMESTAMP(), legacy.updated_at = UTC_TIMESTAMP()
                 WHERE legacy.page_type = \'custom\'
                   AND legacy.slug_path = :legacySlug
                   AND legacy.deleted_at IS NULL
                   AND EXISTS (
                       SELECT 1 FROM pages current_page
                       WHERE current_page.page_type = \'custom\'
                         AND current_page.slug_path = :currentSlug
                         AND current_page.deleted_at IS NULL
                   )',
                [
                    'legacySlug'  => $legacySlug,
                    'currentSlug' => $currentSlug,
                ],
            );
        }
    }

    #[Override]
    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('pages')) {
            return;
        }

        $this->addSql(
            'UPDATE pages
             SET deleted_at = NULL, updated_at = UTC_TIMESTAMP()
             WHERE page_type = \'custom\'
               AND slug_path IN (\'delivery\', \'payment\', \'return\')
               AND deleted_at IS NOT NULL',
        );
    }
}
