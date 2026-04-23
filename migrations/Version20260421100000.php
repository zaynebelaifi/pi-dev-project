<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260421100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add payment_method column to orders table if missing';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("SET @has_payment_method = (
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'orders'
              AND COLUMN_NAME = 'payment_method'
        )");
        $this->addSql("SET @sql = IF(@has_payment_method = 0, 'ALTER TABLE orders ADD payment_method TEXT DEFAULT NULL', 'SELECT 1')");
        $this->addSql('PREPARE stmt FROM @sql');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("SET @has_payment_method = (
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'orders'
              AND COLUMN_NAME = 'payment_method'
        )");
        $this->addSql("SET @sql = IF(@has_payment_method > 0, 'ALTER TABLE orders DROP COLUMN payment_method', 'SELECT 1')");
        $this->addSql('PREPARE stmt FROM @sql');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');
    }
}
