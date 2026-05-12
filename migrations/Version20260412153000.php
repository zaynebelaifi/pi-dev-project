<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260412153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure wasterecord.id is AUTO_INCREMENT to avoid duplicate PK inserts';
    }

    public function up(Schema $schema): void
    {
        // Only modify the column to AUTO_INCREMENT when it's not already auto-increment
        $this->addSql("SET @is_ai = (SELECT EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wasterecord' AND COLUMN_NAME = 'id')");
        $this->addSql("SET @sql = IF(LOCATE('auto_increment', LOWER(@is_ai)) = 0, 'ALTER TABLE `wasterecord` MODIFY `id` INT NOT NULL AUTO_INCREMENT', 'SELECT 1')");
        $this->addSql('PREPARE stmt FROM @sql');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');
    }

    public function down(Schema $schema): void
    {
        // Reverting may remove AUTO_INCREMENT; only attempt if present
        $this->addSql("SET @is_ai = (SELECT EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wasterecord' AND COLUMN_NAME = 'id')");
        $this->addSql("SET @sql = IF(LOCATE('auto_increment', LOWER(@is_ai)) > 0, 'ALTER TABLE `wasterecord` MODIFY `id` INT NOT NULL', 'SELECT 1')");
        $this->addSql('PREPARE stmt FROM @sql');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');
    }
}
