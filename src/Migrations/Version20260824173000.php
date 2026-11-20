<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Override;

final class Version20260824173000 extends AbstractMigration
{
    #[Override]
    public function getDescription(): string
    {
        return 'Store Bitrix24 deal id for feedback requests.';
    }

    #[Override]
    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('feedback_requests')) {
            return;
        }

        $table = $schema->getTable('feedback_requests');
        if (!$table->hasColumn('bitrix_deal_id')) {
            $this->addSql('ALTER TABLE feedback_requests ADD bitrix_deal_id INT DEFAULT NULL');
            $this->addSql('CREATE INDEX idx_feedback_bitrix_deal_id ON feedback_requests (bitrix_deal_id)');
        }
    }

    #[Override]
    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('feedback_requests')) {
            return;
        }

        $table = $schema->getTable('feedback_requests');
        if ($table->hasIndex('idx_feedback_bitrix_deal_id')) {
            $this->addSql('DROP INDEX idx_feedback_bitrix_deal_id ON feedback_requests');
        }

        if ($table->hasColumn('bitrix_deal_id')) {
            $this->addSql('ALTER TABLE feedback_requests DROP bitrix_deal_id');
        }
    }
}
