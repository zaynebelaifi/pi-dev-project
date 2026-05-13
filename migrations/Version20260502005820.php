<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260502005820 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE delivery_feature (id INT AUTO_INCREMENT NOT NULL, delivery_id INT NOT NULL, features JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', created_at DATETIME NOT NULL, INDEX IDX_15C9CAB812136921 (delivery_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE delivery_feature ADD CONSTRAINT FK_15C9CAB812136921 FOREIGN KEY (delivery_id) REFERENCES delivery (delivery_id)');
        $this->addSql('ALTER TABLE assignment_history DROP FOREIGN KEY FK_ASSIGNMENT_HISTORY_CAR');
        $this->addSql('ALTER TABLE assignment_history DROP FOREIGN KEY FK_ASSIGNMENT_HISTORY_DELIVERY_MAN');
        $this->addSql('ALTER TABLE assignment_history DROP FOREIGN KEY FK_ASSIGNMENT_HISTORY_ASSIGNED_BY');
        $this->addSql('ALTER TABLE audit_log DROP FOREIGN KEY FK_AUDIT_LOG_ACTOR');
        $this->addSql('ALTER TABLE book DROP FOREIGN KEY FK_CBE5A331F675F31B');
        $this->addSql('ALTER TABLE donation_event_item DROP FOREIGN KEY FK_778D4F26126F525E');
        $this->addSql('ALTER TABLE donation_event_item DROP FOREIGN KEY FK_778D4F2671F7E88B');
        $this->addSql('ALTER TABLE event_registration DROP FOREIGN KEY FK_A6A2D3B8837167D6');
        $this->addSql('ALTER TABLE gps_log DROP FOREIGN KEY FK_GPS_LOG_DELIVERY_MAN');
        $this->addSql('ALTER TABLE gps_log DROP FOREIGN KEY FK_GPS_LOG_CAR');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_NOTIFICATION_RECIPIENT');
        $this->addSql('ALTER TABLE password_reset_token DROP FOREIGN KEY FK_5A6E2B3EA76ED395');
        $this->addSql('ALTER TABLE ratings DROP FOREIGN KEY FK_CEB607C9BABCF7FB');
        $this->addSql('ALTER TABLE webauthn_credential DROP FOREIGN KEY FK_WEBAUTHN_USER_ID');
        $this->addSql('DROP TABLE assignment_history');
        $this->addSql('DROP TABLE audit_log');
        $this->addSql('DROP TABLE author');
        $this->addSql('DROP TABLE book');
        $this->addSql('DROP TABLE delivery_reviews');
        $this->addSql('DROP TABLE donation_event_item');
        $this->addSql('DROP TABLE event_registration');
        $this->addSql('DROP TABLE gps_log');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE password_reset_token');
        $this->addSql('DROP TABLE ratings');
        $this->addSql('DROP TABLE webauthn_credential');
        $this->addSql('ALTER TABLE delivery DROP FOREIGN KEY FK_3781EC1048CD51AF');
        $this->addSql('ALTER TABLE delivery ADD created_by VARCHAR(255) DEFAULT NULL, ADD updated_by VARCHAR(255) DEFAULT NULL, DROP destination_latitude, DROP destination_longitude, CHANGE delivery_id delivery_id INT AUTO_INCREMENT NOT NULL, CHANGE fleet_car_id fleet_car_id INT DEFAULT NULL, CHANGE order_id order_id INT NOT NULL, CHANGE delivery_man_id delivery_man_id INT DEFAULT NULL, CHANGE recipient_name recipient_name VARCHAR(255) DEFAULT NULL, CHANGE recipient_phone recipient_phone VARCHAR(255) DEFAULT NULL, CHANGE status status VARCHAR(255) DEFAULT NULL, CHANGE actual_delivery_date actual_delivery_date DATETIME DEFAULT NULL, CHANGE current_longitude current_longitude NUMERIC(10, 8) DEFAULT NULL, CHANGE delivery_notes delivery_notes LONGTEXT DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL, CHANGE candidate_delivery_men candidate_delivery_men LONGTEXT DEFAULT NULL, CHANGE driver_latitude driver_latitude NUMERIC(10, 8) DEFAULT NULL, CHANGE driver_longitude driver_longitude NUMERIC(10, 8) DEFAULT NULL');
        $this->addSql('ALTER TABLE delivery ADD CONSTRAINT FK_3781EC10FD128646 FOREIGN KEY (delivery_man_id) REFERENCES delivery_man (delivery_man_id)');
        $this->addSql('ALTER TABLE delivery ADD CONSTRAINT FK_3781EC1048CD51AF FOREIGN KEY (fleet_car_id) REFERENCES fleet_car (car_id)');
        $this->addSql('DROP INDEX idx_delivery_man_id ON delivery');
        $this->addSql('CREATE INDEX IDX_3781EC10FD128646 ON delivery (delivery_man_id)');
        $this->addSql('DROP INDEX idx_vehicle_type ON delivery_man');
        $this->addSql('DROP INDEX UNIQ_DELIVERY_MAN_LICENSE_NUMBER ON delivery_man');
        $this->addSql('DROP INDEX phone ON delivery_man');
        $this->addSql('DROP INDEX IDX_DELIVERY_MAN_CURRENT_CAR_ID ON delivery_man');
        $this->addSql('DROP INDEX idx_status ON delivery_man');
        $this->addSql('DROP INDEX email ON delivery_man');
        $this->addSql('DROP INDEX idx_phone ON delivery_man');
        $this->addSql('DROP INDEX vehicle_number ON delivery_man');
        $this->addSql('ALTER TABLE delivery_man ADD created_by VARCHAR(255) DEFAULT NULL, ADD updated_by VARCHAR(255) DEFAULT NULL, ADD email_address VARCHAR(255) DEFAULT NULL, DROP email, DROP latitude, DROP longitude, DROP last_location_update, DROP license_number, DROP license_expiry_date, DROP is_available, DROP current_car_id, DROP average_rating, DROP total_deliveries, CHANGE delivery_man_id delivery_man_id INT AUTO_INCREMENT NOT NULL, CHANGE name name VARCHAR(255) NOT NULL, CHANGE vehicle_type vehicle_type VARCHAR(255) DEFAULT NULL, CHANGE vehicle_number vehicle_number VARCHAR(255) DEFAULT NULL, CHANGE status status VARCHAR(255) DEFAULT NULL, CHANGE rating rating NUMERIC(3, 2) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL, CHANGE phone phone_number VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE dish DROP FOREIGN KEY fk_dish_menu');
        $this->addSql('ALTER TABLE dish DROP FOREIGN KEY fk_dish_menu');
        $this->addSql('ALTER TABLE dish CHANGE menu_id menu_id INT DEFAULT NULL, CHANGE name name VARCHAR(255) NOT NULL, CHANGE base_price base_price NUMERIC(10, 0) NOT NULL, CHANGE available available TINYINT(1) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE dish ADD CONSTRAINT FK_957D8CB8CCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (id)');
        $this->addSql('DROP INDEX fk_dish_menu ON dish');
        $this->addSql('CREATE INDEX IDX_957D8CB8CCD7E912 ON dish (menu_id)');
        $this->addSql('ALTER TABLE dish ADD CONSTRAINT fk_dish_menu FOREIGN KEY (menu_id) REFERENCES menu (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dish_ingredient DROP FOREIGN KEY fk_dish_ingredient_dish');
        $this->addSql('ALTER TABLE dish_ingredient DROP FOREIGN KEY fk_dish_ingredient_ingredient');
        $this->addSql('ALTER TABLE dish_ingredient DROP FOREIGN KEY fk_dish_ingredient_ingredient');
        $this->addSql('ALTER TABLE dish_ingredient CHANGE quantity_required quantity_required NUMERIC(10, 0) NOT NULL');
        $this->addSql('ALTER TABLE dish_ingredient ADD CONSTRAINT FK_77196056148EB0CB FOREIGN KEY (dish_id) REFERENCES dish (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dish_ingredient ADD CONSTRAINT FK_77196056933FE08C FOREIGN KEY (ingredient_id) REFERENCES ingredient (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_dish_ingredient_ingredient ON dish_ingredient');
        $this->addSql('CREATE INDEX IDX_77196056933FE08C ON dish_ingredient (ingredient_id)');
        $this->addSql('ALTER TABLE dish_ingredient ADD CONSTRAINT fk_dish_ingredient_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredient (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX uk_fleet_delivery_man ON fleet_car');
        $this->addSql('ALTER TABLE fleet_car DROP color, DROP year, DROP fuel_type, DROP mileage, DROP registration_date, DROP last_maintenance_date, DROP status, DROP latitude, DROP longitude, DROP last_update, DROP battery_level, DROP fuel_level, DROP is_active, DROP created_at, DROP updated_at, CHANGE car_id car_id INT AUTO_INCREMENT NOT NULL, CHANGE make make VARCHAR(255) NOT NULL, CHANGE model model VARCHAR(255) NOT NULL, CHANGE license_plate license_plate VARCHAR(255) NOT NULL, CHANGE vehicle_type vehicle_type VARCHAR(255) NOT NULL, CHANGE delivery_man_id delivery_man_id INT DEFAULT NULL');
        $this->addSql('DROP INDEX idx_food_donation_event_status_date ON food_donation_event');
        $this->addSql('DROP INDEX idx_event_date ON food_donation_event');
        $this->addSql('DROP INDEX idx_status ON food_donation_event');
        $this->addSql('DROP INDEX idx_delivery_id ON food_donation_event');
        $this->addSql('ALTER TABLE food_donation_event DROP sms_reminder_sent, CHANGE event_date event_date DATE NOT NULL, CHANGE charity_name charity_name VARCHAR(255) NOT NULL, CHANGE status status VARCHAR(50) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE food_donation_items DROP FOREIGN KEY fk_food_donation_items_dish');
        $this->addSql('ALTER TABLE food_donation_items DROP FOREIGN KEY fk_food_donation_items_event');
        $this->addSql('DROP INDEX idx_item_id ON food_donation_items');
        $this->addSql('DROP INDEX IDX_54E57C7BBABCF7FB ON food_donation_items');
        $this->addSql('DROP INDEX idx_ingredient_expiry_stock ON ingredient');
        $this->addSql('ALTER TABLE ingredient CHANGE quantityInStock quantityInStock NUMERIC(10, 0) NOT NULL, CHANGE unit unit VARCHAR(255) NOT NULL, CHANGE minStockLevel minStockLevel NUMERIC(10, 0) NOT NULL, CHANGE unitCost unitCost NUMERIC(10, 0) NOT NULL');
        $this->addSql('ALTER TABLE menu DROP is_active, CHANGE title title VARCHAR(255) NOT NULL, CHANGE isActive isActive TINYINT(1) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY fk_ord_reservation');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY fk_ord_client');
        $this->addSql('DROP INDEX client_id ON orders');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY fk_ord_reservation');
        $this->addSql('ALTER TABLE orders DROP Payment_method, CHANGE order_type order_type VARCHAR(20) NOT NULL, CHANGE status status VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEEB83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (reservation_id) ON DELETE SET NULL');
        $this->addSql('DROP INDEX reservation_id ON orders');
        $this->addSql('CREATE INDEX IDX_E52FFDEEB83297E7 ON orders (reservation_id)');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT fk_ord_reservation FOREIGN KEY (reservation_id) REFERENCES reservation (reservation_id) ON UPDATE CASCADE ON DELETE SET NULL');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY fk_res_client');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY fk_res_table');
        $this->addSql('DROP INDEX client_id ON reservation');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY fk_res_table');
        $this->addSql('ALTER TABLE reservation CHANGE status status VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955ECFF285C FOREIGN KEY (table_id) REFERENCES restaurant_table (table_id)');
        $this->addSql('DROP INDEX table_id ON reservation');
        $this->addSql('CREATE INDEX IDX_42C84955ECFF285C ON reservation (table_id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT fk_res_table FOREIGN KEY (table_id) REFERENCES restaurant_table (table_id) ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE restaurant_table CHANGE status status VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX idx_donation_event_id ON sustainability_metrics');
        $this->addSql('ALTER TABLE sustainability_metrics CHANGE co2_saved_kg co2_saved_kg NUMERIC(10, 0) NOT NULL, CHANGE cost_saved cost_saved NUMERIC(10, 0) DEFAULT NULL, CHANGE calculated_at calculated_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX uk_email_role ON user');
        $this->addSql('ALTER TABLE user DROP full_name, DROP remember_token, DROP remember_token_expiry, DROP phone_number, DROP is_active, DROP is_verified, DROP profile_image, DROP created_at, DROP updated_at, CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE password_hash password_hash VARCHAR(255) NOT NULL, CHANGE role role VARCHAR(255) NOT NULL, CHANGE reference_id reference_id INT DEFAULT NULL, CHANGE phone phone VARCHAR(255) DEFAULT NULL');
        $this->addSql('DROP INDEX email ON user1');
        $this->addSql('ALTER TABLE user1 CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE name name VARCHAR(255) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE role role VARCHAR(255) NOT NULL, CHANGE status status VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX idx_wasterecord_ingredient_date ON wasterecord');
        $this->addSql('ALTER TABLE wasterecord DROP FOREIGN KEY fk_waste_ingredients');
        $this->addSql('ALTER TABLE wasterecord CHANGE quantityWasted quantityWasted NUMERIC(10, 0) NOT NULL');
        $this->addSql('DROP INDEX ingredientid ON wasterecord');
        $this->addSql('CREATE INDEX IDX_90F7A4D85B5CA8A5 ON wasterecord (ingredientId)');
        $this->addSql('ALTER TABLE wasterecord ADD CONSTRAINT fk_waste_ingredients FOREIGN KEY (ingredientId) REFERENCES ingredient (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE assignment_history (id INT AUTO_INCREMENT NOT NULL, car_id BIGINT NOT NULL, delivery_man_id BIGINT NOT NULL, assigned_by_id BIGINT DEFAULT NULL, assigned_at DATETIME NOT NULL, unassigned_at DATETIME DEFAULT NULL, reason VARCHAR(40) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, status VARCHAR(30) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_ASSIGNMENT_HISTORY_CAR_ID (car_id), INDEX IDX_ASSIGNMENT_HISTORY_DELIVERY_MAN_ID (delivery_man_id), INDEX IDX_ASSIGNMENT_HISTORY_ASSIGNED_BY_ID (assigned_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE audit_log (id INT AUTO_INCREMENT NOT NULL, actor_id BIGINT DEFAULT NULL, action VARCHAR(30) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, entity_type VARCHAR(60) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, entity_id INT NOT NULL, changes JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', timestamp DATETIME NOT NULL, ip_address VARCHAR(45) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, user_agent VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_AUDIT_LOG_ACTOR_ID (actor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE author (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(55) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, email VARCHAR(55) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE book (id INT AUTO_INCREMENT NOT NULL, author_id INT NOT NULL, title VARCHAR(55) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, publication_date DATE NOT NULL, enabled TINYINT(1) NOT NULL, INDEX IDX_CBE5A331F675F31B (author_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE delivery_reviews (id INT AUTO_INCREMENT NOT NULL, order_id VARCHAR(64) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, customer_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, customer_email VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, review_text TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, rating INT DEFAULT NULL, sentiment VARCHAR(16) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, confidence DOUBLE PRECISION DEFAULT NULL, summary VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, routed_to VARCHAR(32) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, support_ticket TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX ix_delivery_reviews_id (id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE donation_event_item (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, item_id INT NOT NULL, quantity INT NOT NULL, INDEX IDX_778D4F2671F7E88B (event_id), INDEX IDX_778D4F26126F525E (item_id), UNIQUE INDEX uniq_event_item_pair (event_id, item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE event_registration (id INT AUTO_INCREMENT NOT NULL, donation_event_id INT NOT NULL, user_id INT NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX uniq_event_user_registration (donation_event_id, user_id), INDEX IDX_A6A2D3B8837167D6 (donation_event_id), INDEX IDX_A6A2D3B8A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE gps_log (id INT AUTO_INCREMENT NOT NULL, car_id BIGINT NOT NULL, delivery_man_id BIGINT DEFAULT NULL, latitude NUMERIC(10, 6) NOT NULL, longitude NUMERIC(10, 6) NOT NULL, accuracy INT DEFAULT NULL, altitude DOUBLE PRECISION DEFAULT NULL, speed DOUBLE PRECISION DEFAULT NULL, bearing DOUBLE PRECISION DEFAULT NULL, timestamp DATETIME NOT NULL, source VARCHAR(30) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_GPS_LOG_CAR_ID (car_id), INDEX IDX_GPS_LOG_DELIVERY_MAN_ID (delivery_man_id), INDEX idx_gps_log_car_timestamp (car_id, timestamp), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, recipient_id BIGINT NOT NULL, type VARCHAR(30) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, title VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, message LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, related_entity VARCHAR(60) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, related_entity_id INT DEFAULT NULL, is_read TINYINT(1) DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, read_at DATETIME DEFAULT NULL, INDEX IDX_NOTIFICATION_RECIPIENT_ID (recipient_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE password_reset_token (id INT AUTO_INCREMENT NOT NULL, user_id BIGINT NOT NULL, token_hash VARCHAR(64) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL, used_at DATETIME DEFAULT NULL, INDEX IDX_5A6E2B3EA76ED395 (user_id), INDEX idx_password_reset_token_hash (token_hash), INDEX idx_password_reset_expires_at (expires_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE ratings (rating_id INT AUTO_INCREMENT NOT NULL, donation_event_id INT NOT NULL, user_id INT NOT NULL, event_rating INT DEFAULT NULL, food_rating INT DEFAULT NULL, comment VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, INDEX IDX_CEB607C9A76ED395 (user_id), INDEX IDX_CEB607C9BABCF7FB (donation_event_id), PRIMARY KEY(rating_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE webauthn_credential (id INT AUTO_INCREMENT NOT NULL, user_id BIGINT DEFAULT NULL, credential_id VARCHAR(512) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, user_handle VARCHAR(128) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, source_json LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, public_key LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, counter INT DEFAULT 0 NOT NULL, INDEX idx_webauthn_user_id (user_id), UNIQUE INDEX uniq_webauthn_credential_id (credential_id), INDEX idx_webauthn_user_handle (user_handle), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE assignment_history ADD CONSTRAINT FK_ASSIGNMENT_HISTORY_CAR FOREIGN KEY (car_id) REFERENCES fleet_car (car_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE assignment_history ADD CONSTRAINT FK_ASSIGNMENT_HISTORY_DELIVERY_MAN FOREIGN KEY (delivery_man_id) REFERENCES delivery_man (delivery_man_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE assignment_history ADD CONSTRAINT FK_ASSIGNMENT_HISTORY_ASSIGNED_BY FOREIGN KEY (assigned_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE audit_log ADD CONSTRAINT FK_AUDIT_LOG_ACTOR FOREIGN KEY (actor_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE book ADD CONSTRAINT FK_CBE5A331F675F31B FOREIGN KEY (author_id) REFERENCES author (id)');
        $this->addSql('ALTER TABLE donation_event_item ADD CONSTRAINT FK_778D4F26126F525E FOREIGN KEY (item_id) REFERENCES dish (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE donation_event_item ADD CONSTRAINT FK_778D4F2671F7E88B FOREIGN KEY (event_id) REFERENCES food_donation_event (donation_event_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_registration ADD CONSTRAINT FK_A6A2D3B8837167D6 FOREIGN KEY (donation_event_id) REFERENCES food_donation_event (donation_event_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE gps_log ADD CONSTRAINT FK_GPS_LOG_DELIVERY_MAN FOREIGN KEY (delivery_man_id) REFERENCES delivery_man (delivery_man_id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE gps_log ADD CONSTRAINT FK_GPS_LOG_CAR FOREIGN KEY (car_id) REFERENCES fleet_car (car_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_NOTIFICATION_RECIPIENT FOREIGN KEY (recipient_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE password_reset_token ADD CONSTRAINT FK_5A6E2B3EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ratings ADD CONSTRAINT FK_CEB607C9BABCF7FB FOREIGN KEY (donation_event_id) REFERENCES food_donation_event (donation_event_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE webauthn_credential ADD CONSTRAINT FK_WEBAUTHN_USER_ID FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE delivery_feature DROP FOREIGN KEY FK_15C9CAB812136921');
        $this->addSql('DROP TABLE delivery_feature');
        $this->addSql('ALTER TABLE delivery DROP FOREIGN KEY FK_3781EC10FD128646');
        $this->addSql('ALTER TABLE delivery DROP FOREIGN KEY FK_3781EC1048CD51AF');
        $this->addSql('ALTER TABLE delivery DROP FOREIGN KEY FK_3781EC10FD128646');
        $this->addSql('ALTER TABLE delivery ADD destination_latitude NUMERIC(10, 6) DEFAULT NULL, ADD destination_longitude NUMERIC(10, 6) DEFAULT NULL, DROP created_by, DROP updated_by, CHANGE delivery_id delivery_id BIGINT AUTO_INCREMENT NOT NULL, CHANGE delivery_man_id delivery_man_id BIGINT DEFAULT NULL, CHANGE fleet_car_id fleet_car_id BIGINT DEFAULT NULL, CHANGE order_id order_id BIGINT NOT NULL, CHANGE recipient_name recipient_name VARCHAR(100) DEFAULT NULL, CHANGE recipient_phone recipient_phone VARCHAR(20) DEFAULT NULL, CHANGE status status VARCHAR(50) DEFAULT \'PENDING\', CHANGE actual_delivery_date actual_delivery_date DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE current_longitude current_longitude NUMERIC(11, 8) DEFAULT NULL, CHANGE driver_latitude driver_latitude NUMERIC(10, 6) DEFAULT NULL, CHANGE driver_longitude driver_longitude NUMERIC(10, 6) DEFAULT NULL, CHANGE delivery_notes delivery_notes TEXT DEFAULT NULL, CHANGE candidate_delivery_men candidate_delivery_men TEXT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE delivery ADD CONSTRAINT FK_3781EC1048CD51AF FOREIGN KEY (fleet_car_id) REFERENCES fleet_car (car_id) ON DELETE SET NULL');
        $this->addSql('DROP INDEX idx_3781ec10fd128646 ON delivery');
        $this->addSql('CREATE INDEX idx_delivery_man_id ON delivery (delivery_man_id)');
        $this->addSql('ALTER TABLE delivery ADD CONSTRAINT FK_3781EC10FD128646 FOREIGN KEY (delivery_man_id) REFERENCES delivery_man (delivery_man_id)');
        $this->addSql('ALTER TABLE delivery_man ADD email VARCHAR(100) DEFAULT NULL, ADD latitude NUMERIC(10, 6) DEFAULT NULL, ADD longitude NUMERIC(10, 6) DEFAULT NULL, ADD last_location_update DATETIME DEFAULT NULL, ADD license_number VARCHAR(50) DEFAULT NULL, ADD license_expiry_date DATE DEFAULT NULL, ADD is_available TINYINT(1) DEFAULT 1 NOT NULL, ADD current_car_id BIGINT DEFAULT NULL, ADD average_rating DOUBLE PRECISION DEFAULT NULL, ADD total_deliveries INT DEFAULT 0 NOT NULL, DROP created_by, DROP updated_by, DROP email_address, CHANGE delivery_man_id delivery_man_id BIGINT AUTO_INCREMENT NOT NULL, CHANGE name name VARCHAR(100) NOT NULL, CHANGE vehicle_type vehicle_type VARCHAR(50) DEFAULT NULL, CHANGE vehicle_number vehicle_number VARCHAR(50) DEFAULT NULL, CHANGE status status VARCHAR(50) DEFAULT \'ACTIVE\', CHANGE rating rating NUMERIC(3, 2) DEFAULT \'0.00\', CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE phone_number phone VARCHAR(20) NOT NULL');
        $this->addSql('CREATE INDEX idx_vehicle_type ON delivery_man (vehicle_type)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DELIVERY_MAN_LICENSE_NUMBER ON delivery_man (license_number)');
        $this->addSql('CREATE UNIQUE INDEX phone ON delivery_man (phone)');
        $this->addSql('CREATE INDEX IDX_DELIVERY_MAN_CURRENT_CAR_ID ON delivery_man (current_car_id)');
        $this->addSql('CREATE INDEX idx_status ON delivery_man (status)');
        $this->addSql('CREATE UNIQUE INDEX email ON delivery_man (email)');
        $this->addSql('CREATE INDEX idx_phone ON delivery_man (phone)');
        $this->addSql('CREATE UNIQUE INDEX vehicle_number ON delivery_man (vehicle_number)');
        $this->addSql('ALTER TABLE dish DROP FOREIGN KEY FK_957D8CB8CCD7E912');
        $this->addSql('ALTER TABLE dish DROP FOREIGN KEY FK_957D8CB8CCD7E912');
        $this->addSql('ALTER TABLE dish CHANGE menu_id menu_id INT NOT NULL, CHANGE name name VARCHAR(120) NOT NULL, CHANGE base_price base_price NUMERIC(10, 2) NOT NULL, CHANGE available available TINYINT(1) DEFAULT 1 NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE dish ADD CONSTRAINT fk_dish_menu FOREIGN KEY (menu_id) REFERENCES menu (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_957d8cb8ccd7e912 ON dish');
        $this->addSql('CREATE INDEX fk_dish_menu ON dish (menu_id)');
        $this->addSql('ALTER TABLE dish ADD CONSTRAINT FK_957D8CB8CCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (id)');
        $this->addSql('ALTER TABLE dish_ingredient DROP FOREIGN KEY FK_77196056148EB0CB');
        $this->addSql('ALTER TABLE dish_ingredient DROP FOREIGN KEY FK_77196056933FE08C');
        $this->addSql('ALTER TABLE dish_ingredient DROP FOREIGN KEY FK_77196056933FE08C');
        $this->addSql('ALTER TABLE dish_ingredient CHANGE quantity_required quantity_required DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE dish_ingredient ADD CONSTRAINT fk_dish_ingredient_dish FOREIGN KEY (dish_id) REFERENCES dish (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dish_ingredient ADD CONSTRAINT fk_dish_ingredient_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredient (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_77196056933fe08c ON dish_ingredient');
        $this->addSql('CREATE INDEX idx_dish_ingredient_ingredient ON dish_ingredient (ingredient_id)');
        $this->addSql('ALTER TABLE dish_ingredient ADD CONSTRAINT FK_77196056933FE08C FOREIGN KEY (ingredient_id) REFERENCES ingredient (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fleet_car ADD color VARCHAR(255) DEFAULT NULL, ADD year INT DEFAULT NULL, ADD fuel_type VARCHAR(20) DEFAULT NULL, ADD mileage INT DEFAULT NULL, ADD registration_date DATE DEFAULT NULL, ADD last_maintenance_date DATE DEFAULT NULL, ADD status VARCHAR(30) DEFAULT \'AVAILABLE\' NOT NULL, ADD latitude NUMERIC(10, 6) DEFAULT NULL, ADD longitude NUMERIC(10, 6) DEFAULT NULL, ADD last_update DATETIME DEFAULT NULL, ADD battery_level INT DEFAULT NULL, ADD fuel_level INT DEFAULT NULL, ADD is_active TINYINT(1) DEFAULT 1 NOT NULL, ADD created_at DATETIME DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL, CHANGE car_id car_id BIGINT NOT NULL, CHANGE make make VARCHAR(128) DEFAULT \'\' NOT NULL, CHANGE model model VARCHAR(128) DEFAULT \'\' NOT NULL, CHANGE license_plate license_plate VARCHAR(64) DEFAULT \'\' NOT NULL, CHANGE vehicle_type vehicle_type VARCHAR(64) DEFAULT \'Sedan\' NOT NULL, CHANGE delivery_man_id delivery_man_id BIGINT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX uk_fleet_delivery_man ON fleet_car (delivery_man_id)');
        $this->addSql('ALTER TABLE food_donation_event ADD sms_reminder_sent TINYINT(1) DEFAULT 0 NOT NULL, CHANGE event_date event_date DATETIME NOT NULL, CHANGE charity_name charity_name VARCHAR(100) NOT NULL, CHANGE status status VARCHAR(50) DEFAULT \'Scheduled\' NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('CREATE INDEX idx_food_donation_event_status_date ON food_donation_event (status, event_date)');
        $this->addSql('CREATE INDEX idx_event_date ON food_donation_event (event_date)');
        $this->addSql('CREATE INDEX idx_status ON food_donation_event (status)');
        $this->addSql('CREATE INDEX idx_delivery_id ON food_donation_event (delivery_id)');
        $this->addSql('ALTER TABLE food_donation_items ADD CONSTRAINT fk_food_donation_items_dish FOREIGN KEY (item_id) REFERENCES dish (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE food_donation_items ADD CONSTRAINT fk_food_donation_items_event FOREIGN KEY (donation_event_id) REFERENCES food_donation_event (donation_event_id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('CREATE INDEX idx_item_id ON food_donation_items (item_id)');
        $this->addSql('CREATE INDEX IDX_54E57C7BBABCF7FB ON food_donation_items (donation_event_id)');
        $this->addSql('ALTER TABLE ingredient CHANGE quantityInStock quantityInStock DOUBLE PRECISION NOT NULL, CHANGE unit unit VARCHAR(50) NOT NULL, CHANGE minStockLevel minStockLevel DOUBLE PRECISION NOT NULL, CHANGE unitCost unitCost NUMERIC(10, 2) NOT NULL');
        $this->addSql('CREATE INDEX idx_ingredient_expiry_stock ON ingredient (expiryDate, quantityInStock)');
        $this->addSql('ALTER TABLE menu ADD is_active TINYINT(1) DEFAULT 1 NOT NULL, CHANGE title title VARCHAR(120) NOT NULL, CHANGE isActive isActive TINYINT(1) DEFAULT 1 NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEEB83297E7');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEEB83297E7');
        $this->addSql('ALTER TABLE orders ADD Payment_method TEXT DEFAULT NULL, CHANGE order_type order_type VARCHAR(255) NOT NULL, CHANGE status status VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT fk_ord_reservation FOREIGN KEY (reservation_id) REFERENCES reservation (reservation_id) ON UPDATE CASCADE ON DELETE SET NULL');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT fk_ord_client FOREIGN KEY (client_id) REFERENCES user1 (id) ON UPDATE CASCADE');
        $this->addSql('CREATE INDEX client_id ON orders (client_id)');
        $this->addSql('DROP INDEX idx_e52ffdeeb83297e7 ON orders');
        $this->addSql('CREATE INDEX reservation_id ON orders (reservation_id)');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEEB83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (reservation_id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955ECFF285C');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955ECFF285C');
        $this->addSql('ALTER TABLE reservation CHANGE status status VARCHAR(255) DEFAULT \'CONFIRMED\' NOT NULL');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT fk_res_client FOREIGN KEY (client_id) REFERENCES user1 (id) ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT fk_res_table FOREIGN KEY (table_id) REFERENCES restaurant_table (table_id) ON UPDATE CASCADE');
        $this->addSql('CREATE INDEX client_id ON reservation (client_id)');
        $this->addSql('DROP INDEX idx_42c84955ecff285c ON reservation');
        $this->addSql('CREATE INDEX table_id ON reservation (table_id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955ECFF285C FOREIGN KEY (table_id) REFERENCES restaurant_table (table_id)');
        $this->addSql('ALTER TABLE restaurant_table CHANGE status status VARCHAR(255) DEFAULT \'AVAILABLE\' NOT NULL');
        $this->addSql('ALTER TABLE sustainability_metrics CHANGE co2_saved_kg co2_saved_kg NUMERIC(10, 2) NOT NULL, CHANGE cost_saved cost_saved NUMERIC(12, 2) DEFAULT NULL, CHANGE calculated_at calculated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('CREATE INDEX idx_donation_event_id ON sustainability_metrics (donation_event_id)');
        $this->addSql('ALTER TABLE user ADD full_name VARCHAR(255) DEFAULT NULL, ADD remember_token VARCHAR(32) DEFAULT NULL, ADD remember_token_expiry DATETIME DEFAULT NULL, ADD phone_number VARCHAR(30) DEFAULT NULL, ADD is_active TINYINT(1) DEFAULT 1 NOT NULL, ADD is_verified TINYINT(1) DEFAULT 0 NOT NULL, ADD profile_image VARCHAR(255) DEFAULT NULL, ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE id id BIGINT AUTO_INCREMENT NOT NULL, CHANGE password_hash password_hash VARCHAR(512) NOT NULL, CHANGE role role VARCHAR(32) NOT NULL, CHANGE reference_id reference_id BIGINT DEFAULT NULL, CHANGE phone phone VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX uk_email_role ON user (email, role)');
        $this->addSql('ALTER TABLE user1 CHANGE id id INT NOT NULL, CHANGE name name VARCHAR(100) NOT NULL, CHANGE email email VARCHAR(150) NOT NULL, CHANGE role role VARCHAR(20) NOT NULL, CHANGE status status VARCHAR(20) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX email ON user1 (email)');
        $this->addSql('ALTER TABLE wasterecord DROP FOREIGN KEY FK_90F7A4D85B5CA8A5');
        $this->addSql('ALTER TABLE wasterecord CHANGE quantityWasted quantityWasted DOUBLE PRECISION NOT NULL');
        $this->addSql('CREATE INDEX idx_wasterecord_ingredient_date ON wasterecord (ingredientId, date)');
        $this->addSql('DROP INDEX idx_90f7a4d85b5ca8a5 ON wasterecord');
        $this->addSql('CREATE INDEX ingredientId ON wasterecord (ingredientId)');
        $this->addSql('ALTER TABLE wasterecord ADD CONSTRAINT FK_90F7A4D85B5CA8A5 FOREIGN KEY (ingredientId) REFERENCES ingredient (id)');
    }
}
