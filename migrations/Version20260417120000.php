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
        // add columns for candidate assignment flow
        $this->addSql("ALTER TABLE delivery ADD candidate_delivery_men TEXT DEFAULT NULL");
        $this->addSql("ALTER TABLE delivery ADD candidate_index INT DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE delivery DROP COLUMN candidate_delivery_men');
        $this->addSql('ALTER TABLE delivery DROP COLUMN candidate_index');
    }
}
