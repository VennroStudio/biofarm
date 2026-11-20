<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261119192034 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store the optional main product for each catalogue group.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product_groups ADD main_product_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product_groups DROP main_product_id');
    }
}
