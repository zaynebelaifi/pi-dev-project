<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260412160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rebuild wasterecord table with AUTO_INCREMENT id preserving existing data';
    }

    public function up(Schema $schema): void
    {
        // Create a new table with proper AUTO_INCREMENT primary key
        $this->addSql("CREATE TABLE IF NOT EXISTS wasterecord_new (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            ingredientId INT NOT NULL,
            quantityWasted DECIMAL(10,2) NOT NULL,
            wasteType VARCHAR(255) NOT NULL,
            date DATE NOT NULL,
            reason VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Copy data from old table if it exists
        $this->addSql("INSERT INTO wasterecord_new (ingredientId, quantityWasted, wasteType, date, reason)
            SELECT ingredientId, quantityWasted, wasteType, date, reason FROM wasterecord");

        // Drop old table and rename new to original
        $this->addSql('DROP TABLE IF EXISTS wasterecord');
        $this->addSql('RENAME TABLE wasterecord_new TO wasterecord');

        // Recreate index and foreign key to ingredient if possible
        $this->addSql('ALTER TABLE wasterecord ADD INDEX IDX_90F7A4D85B5CA8A5 (ingredientId)');
        $this->addSql("ALTER TABLE wasterecord ADD CONSTRAINT FK_90F7A4D85B5CA8A5 FOREIGN KEY (ingredientId) REFERENCES ingredient (id) ON DELETE CASCADE");
    }

    public function down(Schema $schema): void
    {
        // Attempt to restore previous table name by creating a backup copy without AUTO_INCREMENT
        $this->addSql("CREATE TABLE IF NOT EXISTS wasterecord_backup (
            id INT NOT NULL,
            ingredientId INT NOT NULL,
            quantityWasted DECIMAL(10,2) NOT NULL,
            wasteType VARCHAR(255) NOT NULL,
            date DATE NOT NULL,
            reason VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql('INSERT INTO wasterecord_backup (id, ingredientId, quantityWasted, wasteType, date, reason) SELECT id, ingredientId, quantityWasted, wasteType, date, reason FROM wasterecord');
        $this->addSql('DROP TABLE IF EXISTS wasterecord');
        $this->addSql('RENAME TABLE wasterecord_backup TO wasterecord');
    }
}
