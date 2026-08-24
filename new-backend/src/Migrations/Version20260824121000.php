<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Override;

final class Version20260824121000 extends AbstractMigration
{
    private const array PURPOSE_SLUGS = [
        'dlya-antioksidantnoy-zaschity'   => 'antioksidantnaya-zaschita',
        'dlya-antiparazitarnoy-programmy' => 'antiparazitarnaya-programma',
        'dlya-dykhaniya'                  => 'dykhanie',
        'dlya-nervnoy-sistemy'            => 'nervnaya-sistema',
        'dlya-podzheludochnoy'            => 'podzheludochnaya',
        'dlya-zhenskogo-zdorovya'         => 'zhenskoe-zdorove',
    ];

    #[Override]
    public function getDescription(): string
    {
        return 'Normalize additional purpose attribute slugs.';
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
