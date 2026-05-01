<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add missing columns to user table
 */
final class Version20260408200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing columns to user table';
    }

    public function up(Schema $schema): void
    {
        // Check if columns exist before adding them
        // Use IF NOT EXISTS to avoid failing when columns are already present
        $this->addSql('ALTER TABLE `user` ADD COLUMN IF NOT EXISTS first_name VARCHAR(255) DEFAULT NULL, ADD COLUMN IF NOT EXISTS last_name VARCHAR(255) DEFAULT NULL, ADD COLUMN IF NOT EXISTS phone VARCHAR(255) DEFAULT NULL, ADD COLUMN IF NOT EXISTS address VARCHAR(255) DEFAULT NULL, ADD COLUMN IF NOT EXISTS banned TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP COLUMN first_name, DROP COLUMN last_name, DROP COLUMN phone, DROP COLUMN address, DROP COLUMN banned');
    }
}
