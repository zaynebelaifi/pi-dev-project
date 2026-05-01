<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260412190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create reservation table if missing';
    }

    public function up(Schema $schema): void
    {
        // Create reservation table if it does not exist
        $this->addSql("CREATE TABLE IF NOT EXISTS reservation (
            reservation_id INT AUTO_INCREMENT NOT NULL,
            client_id INT NOT NULL,
            table_id INT NOT NULL,
            reservation_date DATE NOT NULL,
            reservation_time TIME NOT NULL,
            number_of_guests INT NOT NULL,
            status VARCHAR(255) NOT NULL DEFAULT 'CONFIRMED',
            PRIMARY KEY(reservation_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        // Add index for table_id if restaurant_table exists (FK skipped to avoid schema mismatch issues)
        $this->addSql("SET @has_table = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'restaurant_table')");
        $this->addSql("SET @sql = IF(@has_table > 0, 'ALTER TABLE reservation ADD INDEX IDX_reservation_table_id (table_id)', 'SELECT 1')");
        $this->addSql('PREPARE stmt FROM @sql');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS reservation');
    }
}
