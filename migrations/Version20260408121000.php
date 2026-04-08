<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260408121000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align user table with User entity: add first_name, last_name, banned and backfill from full_name';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on MySQL.');

        $this->addSql("ALTER TABLE `user` ADD COLUMN IF NOT EXISTS first_name VARCHAR(255) DEFAULT NULL");
        $this->addSql("ALTER TABLE `user` ADD COLUMN IF NOT EXISTS last_name VARCHAR(255) DEFAULT NULL");
        $this->addSql("ALTER TABLE `user` ADD COLUMN IF NOT EXISTS banned TINYINT(1) NOT NULL DEFAULT 0");

        // Backfill split names from legacy full_name only when the new fields are empty.
        $this->addSql(
            "UPDATE `user`\n"
            . "SET\n"
            . "  first_name = CASE\n"
            . "    WHEN (first_name IS NULL OR first_name = '') AND full_name IS NOT NULL AND TRIM(full_name) <> '' THEN SUBSTRING_INDEX(TRIM(full_name), ' ', 1)\n"
            . "    ELSE first_name\n"
            . "  END,\n"
            . "  last_name = CASE\n"
            . "    WHEN (last_name IS NULL OR last_name = '') AND full_name IS NOT NULL AND TRIM(full_name) <> '' THEN\n"
            . "      CASE\n"
            . "        WHEN LOCATE(' ', TRIM(full_name)) > 0 THEN TRIM(SUBSTRING(TRIM(full_name), LOCATE(' ', TRIM(full_name)) + 1))\n"
            . "        ELSE NULL\n"
            . "      END\n"
            . "    ELSE last_name\n"
            . "  END"
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on MySQL.');

        $this->addSql('ALTER TABLE `user` DROP COLUMN first_name, DROP COLUMN last_name, DROP COLUMN banned');
    }
}
