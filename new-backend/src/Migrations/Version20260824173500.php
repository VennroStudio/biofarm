<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Override;

final class Version20260824173500 extends AbstractMigration
{
    #[Override]
    public function getDescription(): string
    {
        return 'Remove obsolete Bitrix24 lead id from feedback requests.';
    }

    #[Override]
    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('feedback_requests')) {
            return;
        }

        $table = $schema->getTable('feedback_requests');
        if ($table->hasIndex('idx_feedback_bitrix_lead')) {
            $this->addSql('DROP INDEX idx_feedback_bitrix_lead ON feedback_requests');
        }

        if ($table->hasColumn('bitrix_lead_id')) {
            $this->addSql('ALTER TABLE feedback_requests DROP bitrix_lead_id');
        }
    }

    #[Override]
    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('feedback_requests')) {
            return;
        }

        $table = $schema->getTable('feedback_requests');
        if (!$table->hasColumn('bitrix_lead_id')) {
            $this->addSql('ALTER TABLE feedback_requests ADD bitrix_lead_id INT DEFAULT NULL');
            $this->addSql('CREATE INDEX idx_feedback_bitrix_lead ON feedback_requests (bitrix_lead_id)');
        }
    }
}
