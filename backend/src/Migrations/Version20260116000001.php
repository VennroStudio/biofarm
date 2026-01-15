<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260116000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove authorId field from blog_posts table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE biofarm_blog_posts DROP COLUMN authorId');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE biofarm_blog_posts ADD authorId INT NOT NULL DEFAULT 1');
    }
}
