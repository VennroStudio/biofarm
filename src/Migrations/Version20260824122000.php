<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Override;

final class Version20260824122000 extends AbstractMigration
{
    #[Override]
    public function getDescription(): string
    {
        return 'Normalize Siberian fir component slug transliteration.';
    }

    #[Override]
    public function up(Schema $schema): void
    {
        $this->addSql(
            "UPDATE attribute_values av
             INNER JOIN attributes a ON a.id = av.attribute_id AND a.slug = 'sostav'
             SET av.slug = 'sibirskaya-pikhta', av.updated_at = UTC_TIMESTAMP()
             WHERE av.slug = 'sibirskaya-pihta'"
        );

        if ($schema->hasTable('components')) {
            $this->addSql("UPDATE components SET slug = 'sibirskaya-pikhta', updated_at = UTC_TIMESTAMP() WHERE slug = 'sibirskaya-pihta'");
        }
    }

    #[Override]
    public function down(Schema $schema): void
    {
        $this->addSql(
            "UPDATE attribute_values av
             INNER JOIN attributes a ON a.id = av.attribute_id AND a.slug = 'sostav'
             SET av.slug = 'sibirskaya-pihta', av.updated_at = UTC_TIMESTAMP()
             WHERE av.slug = 'sibirskaya-pikhta'"
        );

        if ($schema->hasTable('components')) {
            $this->addSql("UPDATE components SET slug = 'sibirskaya-pihta', updated_at = UTC_TIMESTAMP() WHERE slug = 'sibirskaya-pikhta'");
        }
    }
}
