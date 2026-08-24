<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Override;

final class Version20260824120000 extends AbstractMigration
{
    private const array PURPOSE_SLUGS = [
        'dlya-energii'            => 'energiya',
        'dlya-immuniteta'         => 'immunitet',
        'dlya-mozga'              => 'mozg',
        'dlya-muzhskogo-zdorovya' => 'muzhskoe-zdorove',
        'dlya-pecheni'            => 'pechen',
        'dlya-pishchevareniya'    => 'pishchevarenie',
        'dlya-serdtsa-i-sosudov'  => 'serdtse-i-sosudy',
    ];

    #[Override]
    public function getDescription(): string
    {
        return 'Normalize purpose attribute slugs for readable SEO filter URLs.';
    }

    #[Override]
    public function up(Schema $schema): void
    {
        foreach (self::PURPOSE_SLUGS as $oldSlug => $newSlug) {
            $this->addSql(
                "UPDATE attribute_values av
                 INNER JOIN attributes a ON a.id = av.attribute_id AND a.slug = 'dlya'
                 SET av.slug = :newSlug, av.updated_at = UTC_TIMESTAMP()
                 WHERE av.slug = :oldSlug",
                ['oldSlug' => $oldSlug, 'newSlug' => $newSlug],
            );

            if ($schema->hasTable('product_purposes')) {
                $this->addSql(
                    'UPDATE product_purposes SET slug = :newSlug, updated_at = UTC_TIMESTAMP() WHERE slug = :oldSlug',
                    ['oldSlug' => $oldSlug, 'newSlug' => $newSlug],
                );
            }
        }
    }

    #[Override]
    public function down(Schema $schema): void
    {
        foreach (array_flip(self::PURPOSE_SLUGS) as $newSlug => $oldSlug) {
            $this->addSql(
                "UPDATE attribute_values av
                 INNER JOIN attributes a ON a.id = av.attribute_id AND a.slug = 'dlya'
                 SET av.slug = :oldSlug, av.updated_at = UTC_TIMESTAMP()
                 WHERE av.slug = :newSlug",
                ['oldSlug' => $oldSlug, 'newSlug' => $newSlug],
            );

            if ($schema->hasTable('product_purposes')) {
                $this->addSql(
                    'UPDATE product_purposes SET slug = :oldSlug, updated_at = UTC_TIMESTAMP() WHERE slug = :newSlug',
                    ['oldSlug' => $oldSlug, 'newSlug' => $newSlug],
                );
            }
        }
    }
}
