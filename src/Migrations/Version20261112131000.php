<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261112131000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Publish Ozon cards that the old importer hid because they were out of stock.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE products
            SET is_active = 1, published_at = COALESCE(published_at, UTC_TIMESTAMP())
            WHERE deleted_at IS NULL AND is_active = 0 AND availability = 'out_of_stock'
              AND slug REGEXP '^ozon-[0-9]+$' AND ozon_link LIKE 'https://www.ozon.ru/product/%'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Publication may have been changed by an administrator since the migration.');
    }
}
