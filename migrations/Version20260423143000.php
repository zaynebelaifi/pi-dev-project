<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enhance fleet model: extend user/fleet_car/delivery_man and add gps_log, assignment_history, audit_log, notification tables';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('user')) {
            $table = $schema->getTable('user');

            if (!$table->hasColumn('is_active')) {
                $this->addSql('ALTER TABLE user ADD is_active TINYINT(1) NOT NULL DEFAULT 1');
            }

            if (!$table->hasColumn('is_verified')) {
                $this->addSql('ALTER TABLE user ADD is_verified TINYINT(1) NOT NULL DEFAULT 0');
            }

            if (!$table->hasColumn('profile_image')) {
                $this->addSql('ALTER TABLE user ADD profile_image VARCHAR(255) DEFAULT NULL');
            }

            if (!$table->hasColumn('created_at')) {
                $this->addSql('ALTER TABLE user ADD created_at DATETIME DEFAULT NULL');
            }

            if (!$table->hasColumn('updated_at')) {
                $this->addSql('ALTER TABLE user ADD updated_at DATETIME DEFAULT NULL');
            }
        }

        if ($schema->hasTable('fleet_car')) {
            $table = $schema->getTable('fleet_car');

            if (!$table->hasColumn('color')) {
                $this->addSql('ALTER TABLE fleet_car ADD color VARCHAR(255) DEFAULT NULL');
            }
            if (!$table->hasColumn('year')) {
                $this->addSql('ALTER TABLE fleet_car ADD year INT DEFAULT NULL');
            }
            if (!$table->hasColumn('fuel_type')) {
                $this->addSql('ALTER TABLE fleet_car ADD fuel_type VARCHAR(20) DEFAULT NULL');
            }
            if (!$table->hasColumn('mileage')) {
                $this->addSql('ALTER TABLE fleet_car ADD mileage INT DEFAULT NULL');
            }
            if (!$table->hasColumn('registration_date')) {
                $this->addSql('ALTER TABLE fleet_car ADD registration_date DATE DEFAULT NULL');
            }
            if (!$table->hasColumn('last_maintenance_date')) {
                $this->addSql('ALTER TABLE fleet_car ADD last_maintenance_date DATE DEFAULT NULL');
            }
            if (!$table->hasColumn('status')) {
                $this->addSql("ALTER TABLE fleet_car ADD status VARCHAR(30) NOT NULL DEFAULT 'AVAILABLE'");
            }
            if (!$table->hasColumn('latitude')) {
                $this->addSql('ALTER TABLE fleet_car ADD latitude NUMERIC(10,6) DEFAULT NULL');
            }
            if (!$table->hasColumn('longitude')) {
                $this->addSql('ALTER TABLE fleet_car ADD longitude NUMERIC(10,6) DEFAULT NULL');
            }
            if (!$table->hasColumn('last_update')) {
                $this->addSql('ALTER TABLE fleet_car ADD last_update DATETIME DEFAULT NULL');
            }
            if (!$table->hasColumn('battery_level')) {
                $this->addSql('ALTER TABLE fleet_car ADD battery_level INT DEFAULT NULL');
            }
            if (!$table->hasColumn('fuel_level')) {
                $this->addSql('ALTER TABLE fleet_car ADD fuel_level INT DEFAULT NULL');
            }
            if (!$table->hasColumn('is_active')) {
                $this->addSql('ALTER TABLE fleet_car ADD is_active TINYINT(1) NOT NULL DEFAULT 1');
            }
            if (!$table->hasColumn('created_at')) {
                $this->addSql('ALTER TABLE fleet_car ADD created_at DATETIME DEFAULT NULL');
            }
            if (!$table->hasColumn('updated_at')) {
                $this->addSql('ALTER TABLE fleet_car ADD updated_at DATETIME DEFAULT NULL');
            }
        }

        if ($schema->hasTable('delivery_man')) {
            $table = $schema->getTable('delivery_man');

            if (!$table->hasColumn('license_number')) {
                $this->addSql('ALTER TABLE delivery_man ADD license_number VARCHAR(50) DEFAULT NULL');
                $this->addSql('CREATE UNIQUE INDEX UNIQ_DELIVERY_MAN_LICENSE_NUMBER ON delivery_man (license_number)');
            }
            if (!$table->hasColumn('license_expiry_date')) {
                $this->addSql('ALTER TABLE delivery_man ADD license_expiry_date DATE DEFAULT NULL');
            }
            if (!$table->hasColumn('is_available')) {
                $this->addSql('ALTER TABLE delivery_man ADD is_available TINYINT(1) NOT NULL DEFAULT 1');
            }
            if (!$table->hasColumn('current_car_id')) {
                $this->addSql('ALTER TABLE delivery_man ADD current_car_id BIGINT DEFAULT NULL');
            } else {
                $this->addSql('ALTER TABLE delivery_man MODIFY current_car_id BIGINT DEFAULT NULL');
            }

            $this->addSql("SET @idx_current_car_exists = (SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'delivery_man' AND INDEX_NAME = 'IDX_DELIVERY_MAN_CURRENT_CAR_ID')");
            $this->addSql("SET @sql = IF(@idx_current_car_exists = 0, 'CREATE INDEX IDX_DELIVERY_MAN_CURRENT_CAR_ID ON delivery_man (current_car_id)', 'SELECT 1')");
            $this->addSql('PREPARE stmt FROM @sql');
            $this->addSql('EXECUTE stmt');
            $this->addSql('DEALLOCATE PREPARE stmt');

            $this->addSql("SET @fk_current_car_exists = (SELECT COUNT(1) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'delivery_man' AND CONSTRAINT_NAME = 'FK_DELIVERY_MAN_CURRENT_CAR' AND CONSTRAINT_TYPE = 'FOREIGN KEY')");
            $this->addSql("SET @sql = IF(@fk_current_car_exists = 0, 'ALTER TABLE delivery_man ADD CONSTRAINT FK_DELIVERY_MAN_CURRENT_CAR FOREIGN KEY (current_car_id) REFERENCES fleet_car (car_id) ON DELETE SET NULL', 'SELECT 1')");
            $this->addSql('PREPARE stmt FROM @sql');
            $this->addSql('EXECUTE stmt');
            $this->addSql('DEALLOCATE PREPARE stmt');

            if (!$table->hasColumn('average_rating')) {
                $this->addSql('ALTER TABLE delivery_man ADD average_rating DOUBLE PRECISION DEFAULT NULL');
            }
            if (!$table->hasColumn('total_deliveries')) {
                $this->addSql('ALTER TABLE delivery_man ADD total_deliveries INT NOT NULL DEFAULT 0');
            }
        }

        if (!$schema->hasTable('gps_log')) {
            $this->addSql('CREATE TABLE gps_log (id INT AUTO_INCREMENT NOT NULL, car_id BIGINT NOT NULL, delivery_man_id BIGINT DEFAULT NULL, latitude NUMERIC(10, 6) NOT NULL, longitude NUMERIC(10, 6) NOT NULL, accuracy INT DEFAULT NULL, altitude DOUBLE PRECISION DEFAULT NULL, speed DOUBLE PRECISION DEFAULT NULL, bearing DOUBLE PRECISION DEFAULT NULL, timestamp DATETIME NOT NULL, source VARCHAR(30) NOT NULL, INDEX IDX_GPS_LOG_CAR_ID (car_id), INDEX IDX_GPS_LOG_DELIVERY_MAN_ID (delivery_man_id), INDEX idx_gps_log_car_timestamp (car_id, timestamp), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE gps_log ADD CONSTRAINT FK_GPS_LOG_CAR FOREIGN KEY (car_id) REFERENCES fleet_car (car_id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE gps_log ADD CONSTRAINT FK_GPS_LOG_DELIVERY_MAN FOREIGN KEY (delivery_man_id) REFERENCES delivery_man (delivery_man_id) ON DELETE SET NULL');
        }

        if (!$schema->hasTable('assignment_history')) {
            $this->addSql("CREATE TABLE assignment_history (id INT AUTO_INCREMENT NOT NULL, car_id BIGINT NOT NULL, delivery_man_id BIGINT NOT NULL, assigned_by_id BIGINT DEFAULT NULL, assigned_at DATETIME NOT NULL, unassigned_at DATETIME DEFAULT NULL, reason VARCHAR(40) NOT NULL, status VARCHAR(30) NOT NULL, INDEX IDX_ASSIGNMENT_HISTORY_CAR_ID (car_id), INDEX IDX_ASSIGNMENT_HISTORY_DELIVERY_MAN_ID (delivery_man_id), INDEX IDX_ASSIGNMENT_HISTORY_ASSIGNED_BY_ID (assigned_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
            $this->addSql('ALTER TABLE assignment_history ADD CONSTRAINT FK_ASSIGNMENT_HISTORY_CAR FOREIGN KEY (car_id) REFERENCES fleet_car (car_id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE assignment_history ADD CONSTRAINT FK_ASSIGNMENT_HISTORY_DELIVERY_MAN FOREIGN KEY (delivery_man_id) REFERENCES delivery_man (delivery_man_id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE assignment_history ADD CONSTRAINT FK_ASSIGNMENT_HISTORY_ASSIGNED_BY FOREIGN KEY (assigned_by_id) REFERENCES user (id) ON DELETE SET NULL');
        }

        if (!$schema->hasTable('audit_log')) {
            $this->addSql('CREATE TABLE audit_log (id INT AUTO_INCREMENT NOT NULL, actor_id BIGINT DEFAULT NULL, action VARCHAR(30) NOT NULL, entity_type VARCHAR(60) NOT NULL, entity_id INT NOT NULL, changes JSON DEFAULT NULL, timestamp DATETIME NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, user_agent VARCHAR(255) DEFAULT NULL, INDEX IDX_AUDIT_LOG_ACTOR_ID (actor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE audit_log ADD CONSTRAINT FK_AUDIT_LOG_ACTOR FOREIGN KEY (actor_id) REFERENCES user (id) ON DELETE SET NULL');
        }

        if (!$schema->hasTable('notification')) {
            $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, recipient_id BIGINT NOT NULL, type VARCHAR(30) NOT NULL, title VARCHAR(150) NOT NULL, message LONGTEXT NOT NULL, related_entity VARCHAR(60) DEFAULT NULL, related_entity_id INT DEFAULT NULL, is_read TINYINT(1) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL, read_at DATETIME DEFAULT NULL, INDEX IDX_NOTIFICATION_RECIPIENT_ID (recipient_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_NOTIFICATION_RECIPIENT FOREIGN KEY (recipient_id) REFERENCES user (id) ON DELETE CASCADE');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('gps_log')) {
            $this->addSql('DROP TABLE gps_log');
        }
        if ($schema->hasTable('assignment_history')) {
            $this->addSql('DROP TABLE assignment_history');
        }
        if ($schema->hasTable('audit_log')) {
            $this->addSql('DROP TABLE audit_log');
        }
        if ($schema->hasTable('notification')) {
            $this->addSql('DROP TABLE notification');
        }
    }
}
