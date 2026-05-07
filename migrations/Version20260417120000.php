<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260417120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add candidate_delivery_men (TEXT) and candidate_index (INT) to delivery table';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('delivery')) {
            return;
        }

        $table = $schema->getTable('delivery');

        if (!$table->hasColumn('candidate_delivery_men')) {
            $this->addSql("ALTER TABLE delivery ADD candidate_delivery_men TEXT DEFAULT NULL");
        }

        if (!$table->hasColumn('candidate_index')) {
            $this->addSql("ALTER TABLE delivery ADD candidate_index INT DEFAULT NULL");
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('delivery')) {
            return;
        }

        $table = $schema->getTable('delivery');

        if ($table->hasColumn('candidate_delivery_men')) {
            $this->addSql('ALTER TABLE delivery DROP COLUMN candidate_delivery_men');
        }

        if ($table->hasColumn('candidate_index')) {
            $this->addSql('ALTER TABLE delivery DROP COLUMN candidate_index');
        }
    }
}
