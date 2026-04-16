<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260407181736 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add delivery fleet car linkage with safe idempotent SQL and compatible bigint FK type';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on MySQL.');

        $this->addSql('ALTER TABLE delivery ADD COLUMN IF NOT EXISTS fleet_car_id BIGINT DEFAULT NULL, ADD COLUMN IF NOT EXISTS license_plate VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE delivery MODIFY fleet_car_id BIGINT DEFAULT NULL');

        $this->addSql("SET @fk_exists = (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'FK_3781EC1048CD51AF')");
        $this->addSql("SET @sql = IF(@fk_exists = 0, 'ALTER TABLE delivery ADD CONSTRAINT FK_3781EC1048CD51AF FOREIGN KEY (fleet_car_id) REFERENCES fleet_car (car_id) ON DELETE SET NULL', 'SELECT 1')");
        $this->addSql('PREPARE stmt FROM @sql');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');

        $this->addSql("SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'delivery' AND INDEX_NAME = 'IDX_3781EC1048CD51AF')");
        $this->addSql("SET @sql = IF(@idx_exists = 0, 'CREATE INDEX IDX_3781EC1048CD51AF ON delivery (fleet_car_id)', 'SELECT 1')");
        $this->addSql('PREPARE stmt FROM @sql');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');

        $this->addSql("SET @uniq_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'delivery' AND INDEX_NAME = 'UNIQ_3781EC10F5AA79D0')");
        $this->addSql("SET @dup_count = (SELECT COUNT(*) FROM (SELECT license_plate FROM delivery WHERE license_plate IS NOT NULL GROUP BY license_plate HAVING COUNT(*) > 1) d)");
        $this->addSql("SET @sql = IF(@uniq_exists = 0 AND @dup_count = 0, 'CREATE UNIQUE INDEX UNIQ_3781EC10F5AA79D0 ON delivery (license_plate)', 'SELECT 1')");
        $this->addSql('PREPARE stmt FROM @sql');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on MySQL.');

        $this->addSql("SET @fk_exists = (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'FK_3781EC1048CD51AF')");
        $this->addSql("SET @sql = IF(@fk_exists = 1, 'ALTER TABLE delivery DROP FOREIGN KEY FK_3781EC1048CD51AF', 'SELECT 1')");
        $this->addSql('PREPARE stmt FROM @sql');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');

        $this->addSql("SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'delivery' AND INDEX_NAME = 'UNIQ_3781EC10F5AA79D0')");
        $this->addSql("SET @sql = IF(@idx_exists = 1, 'DROP INDEX UNIQ_3781EC10F5AA79D0 ON delivery', 'SELECT 1')");
        $this->addSql('PREPARE stmt FROM @sql');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');

        $this->addSql("SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'delivery' AND INDEX_NAME = 'IDX_3781EC1048CD51AF')");
        $this->addSql("SET @sql = IF(@idx_exists = 1, 'DROP INDEX IDX_3781EC1048CD51AF ON delivery', 'SELECT 1')");
        $this->addSql('PREPARE stmt FROM @sql');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');

        $this->addSql('ALTER TABLE delivery DROP COLUMN IF EXISTS fleet_car_id, DROP COLUMN IF EXISTS license_plate');
    }
}
