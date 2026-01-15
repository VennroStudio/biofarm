<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260116000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add authorName field to blog_posts table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE biofarm_blog_posts ADD authorName VARCHAR(255) NOT NULL DEFAULT \'Автор\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE biofarm_blog_posts DROP COLUMN authorName');
    }
}
