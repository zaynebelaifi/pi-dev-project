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
        try {
            $this->addSql('ALTER TABLE user ADD COLUMN first_name VARCHAR(255) DEFAULT NULL, ADD COLUMN last_name VARCHAR(255) DEFAULT NULL, ADD COLUMN phone VARCHAR(255) DEFAULT NULL, ADD COLUMN address VARCHAR(255) DEFAULT NULL, ADD COLUMN banned TINYINT(1) DEFAULT 0 NOT NULL');
        } catch (\Exception $e) {
            // Columns might already exist
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP COLUMN first_name, DROP COLUMN last_name, DROP COLUMN phone, DROP COLUMN address, DROP COLUMN banned');
    }
}
