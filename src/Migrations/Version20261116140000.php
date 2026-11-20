<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261116140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Identify saved Ozon courier addresses and pickup points.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_addresses ADD ozon_delivery_method VARCHAR(32) DEFAULT NULL, ADD ozon_destination_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_addresses DROP ozon_delivery_method, DROP ozon_destination_id');
    }
}
