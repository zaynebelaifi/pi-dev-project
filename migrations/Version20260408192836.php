<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260408192836 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // $this->addSql('CREATE TABLE restaurant_table (table_id INT AUTO_INCREMENT NOT NULL, capacity INT NOT NULL, status VARCHAR(255) NOT NULL, PRIMARY KEY(table_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        // $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        if ($schema->hasTable('notifications')) {
            $this->addSql('ALTER TABLE notifications DROP FOREIGN KEY fk_notification_event');
            $this->addSql('ALTER TABLE notifications DROP FOREIGN KEY fk_notification_user');
            $this->addSql('DROP TABLE notifications');
        }

        if ($schema->hasTable('password_reset_token')) {
            $this->addSql('ALTER TABLE password_reset_token DROP FOREIGN KEY password_reset_token_ibfk_1');
            $this->addSql('DROP TABLE password_reset_token');
        }

        if ($schema->hasTable('ratings')) {
            $this->addSql('ALTER TABLE ratings DROP FOREIGN KEY fk_rating_event');
            $this->addSql('ALTER TABLE ratings DROP FOREIGN KEY fk_rating_user');
            $this->addSql('DROP TABLE ratings');
        }

        if ($schema->hasTable('car')) {
            $this->addSql('DROP TABLE car');
        }
        $this->addSql('ALTER TABLE delivery DROP FOREIGN KEY fk_delivery_man');
        $this->addSql('DROP INDEX uk_order_id ON delivery');
        $this->addSql('DROP INDEX idx_scheduled_date ON delivery');
        $this->addSql('DROP INDEX idx_order_id ON delivery');
        $this->addSql('DROP INDEX idx_status ON delivery');
        $this->addSql('ALTER TABLE delivery DROP FOREIGN KEY fk_delivery_man');
        $this->addSql('ALTER TABLE delivery ADD fleet_car_id INT DEFAULT NULL, ADD cart_items LONGTEXT DEFAULT NULL, ADD order_total NUMERIC(10, 2) DEFAULT NULL, ADD restaurant_rating INT DEFAULT NULL, ADD license_plate VARCHAR(255) DEFAULT NULL, CHANGE delivery_id delivery_id INT AUTO_INCREMENT NOT NULL, CHANGE delivery_man_id delivery_man_id INT DEFAULT NULL, CHANGE order_id order_id INT NOT NULL, CHANGE recipient_name recipient_name VARCHAR(255) DEFAULT NULL, CHANGE recipient_phone recipient_phone VARCHAR(255) DEFAULT NULL, CHANGE status status VARCHAR(255) DEFAULT NULL, CHANGE actual_delivery_date actual_delivery_date DATETIME DEFAULT NULL, CHANGE current_latitude current_latitude NUMERIC(10, 0) DEFAULT NULL, CHANGE current_longitude current_longitude NUMERIC(10, 0) DEFAULT NULL, CHANGE delivery_notes delivery_notes LONGTEXT DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE delivery ADD CONSTRAINT FK_3781EC10FD128646 FOREIGN KEY (delivery_man_id) REFERENCES delivery_man (delivery_man_id)');
        $this->addSql('ALTER TABLE delivery ADD CONSTRAINT FK_3781EC1048CD51AF FOREIGN KEY (fleet_car_id) REFERENCES fleet_car (car_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3781EC10F5AA79D0 ON delivery (license_plate)');
        $this->addSql('CREATE INDEX IDX_3781EC1048CD51AF ON delivery (fleet_car_id)');
        $this->addSql('DROP INDEX idx_delivery_man_id ON delivery');
        $this->addSql('CREATE INDEX IDX_3781EC10FD128646 ON delivery (delivery_man_id)');
        $this->addSql('ALTER TABLE delivery ADD CONSTRAINT fk_delivery_man FOREIGN KEY (delivery_man_id) REFERENCES delivery_man (delivery_man_id) ON DELETE SET NULL');
        $this->addSql('DROP INDEX idx_status ON delivery_man');
        $this->addSql('DROP INDEX phone ON delivery_man');
        $this->addSql('DROP INDEX idx_phone ON delivery_man');
        $this->addSql('DROP INDEX email ON delivery_man');
        $this->addSql('DROP INDEX idx_vehicle_type ON delivery_man');
        $this->addSql('DROP INDEX vehicle_number ON delivery_man');
        $this->addSql('ALTER TABLE delivery_man CHANGE delivery_man_id delivery_man_id INT AUTO_INCREMENT NOT NULL, CHANGE name name VARCHAR(255) NOT NULL, CHANGE phone phone VARCHAR(255) NOT NULL, CHANGE email email VARCHAR(255) DEFAULT NULL, CHANGE vehicle_type vehicle_type VARCHAR(255) DEFAULT NULL, CHANGE vehicle_number vehicle_number VARCHAR(255) DEFAULT NULL, CHANGE status status VARCHAR(255) DEFAULT NULL, CHANGE salary salary NUMERIC(10, 0) DEFAULT NULL, CHANGE rating rating NUMERIC(10, 0) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE dish DROP FOREIGN KEY fk_dish_menu');
        $this->addSql('ALTER TABLE dish DROP FOREIGN KEY fk_dish_menu');
        $this->addSql('ALTER TABLE dish CHANGE menu_id menu_id INT DEFAULT NULL, CHANGE name name VARCHAR(255) NOT NULL, CHANGE base_price base_price NUMERIC(10, 0) NOT NULL, CHANGE available available TINYINT(1) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE dish ADD CONSTRAINT FK_957D8CB8CCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (id)');
        $this->addSql('DROP INDEX fk_dish_menu ON dish');
        $this->addSql('CREATE INDEX IDX_957D8CB8CCD7E912 ON dish (menu_id)');
        $this->addSql('ALTER TABLE dish ADD CONSTRAINT fk_dish_menu FOREIGN KEY (menu_id) REFERENCES menu (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dish_ingredient CHANGE quantity_required quantity_required NUMERIC(10, 0) NOT NULL');
        $this->addSql('ALTER TABLE dish_ingredient ADD CONSTRAINT FK_77196056148EB0CB FOREIGN KEY (dish_id) REFERENCES dish (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dish_ingredient ADD CONSTRAINT FK_77196056933FE08C FOREIGN KEY (ingredient_id) REFERENCES ingredient (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_77196056148EB0CB ON dish_ingredient (dish_id)');
        $this->addSql('DROP INDEX idx_dish_ingredient_ingredient ON dish_ingredient');
        $this->addSql('CREATE INDEX IDX_77196056933FE08C ON dish_ingredient (ingredient_id)');
        $this->addSql('DROP INDEX uk_fleet_delivery_man ON fleet_car');
        $this->addSql('ALTER TABLE fleet_car CHANGE car_id car_id INT AUTO_INCREMENT NOT NULL, CHANGE make make VARCHAR(255) NOT NULL, CHANGE model model VARCHAR(255) NOT NULL, CHANGE license_plate license_plate VARCHAR(255) NOT NULL, CHANGE vehicle_type vehicle_type VARCHAR(255) NOT NULL, CHANGE delivery_man_id delivery_man_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE food_donation_event DROP FOREIGN KEY fk_event_delivery');
        $this->addSql('ALTER TABLE food_donation_event DROP FOREIGN KEY fk_events_delivery');
        $this->addSql('DROP INDEX idx_delivery_id ON food_donation_event');
        $this->addSql('DROP INDEX idx_event_date ON food_donation_event');
        $this->addSql('DROP INDEX idx_status ON food_donation_event');
        $this->addSql('ALTER TABLE food_donation_event CHANGE delivery_id delivery_id INT DEFAULT NULL, CHANGE charity_name charity_name VARCHAR(255) NOT NULL, CHANGE status status VARCHAR(255) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE food_donation_items DROP FOREIGN KEY fk_items_event');
        $this->addSql('ALTER TABLE food_donation_items DROP FOREIGN KEY fk_items_dish');
        $this->addSql('DROP INDEX idx_item_id ON food_donation_items');
        $this->addSql('DROP INDEX IDX_54E57C7BBABCF7FB ON food_donation_items');
        $this->addSql('DROP INDEX `primary` ON food_donation_items');
        $this->addSql('ALTER TABLE food_donation_items CHANGE donation_event_id donation_event_id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE food_donation_items ADD PRIMARY KEY (donation_event_id)');
        $this->addSql('DROP INDEX idx_ingredient_expiry_stock ON ingredient');
        $this->addSql('ALTER TABLE ingredient CHANGE quantityInStock quantityInStock NUMERIC(10, 0) NOT NULL, CHANGE unit unit VARCHAR(255) NOT NULL, CHANGE minStockLevel minStockLevel NUMERIC(10, 0) NOT NULL, CHANGE unitCost unitCost NUMERIC(10, 0) NOT NULL');
        $this->addSql('ALTER TABLE menu ADD is_active TINYINT(1) NOT NULL, DROP isActive, CHANGE title title VARCHAR(255) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE sustainability_metrics DROP FOREIGN KEY fk_metrics_event');
        $this->addSql('DROP INDEX idx_donation_event_id ON sustainability_metrics');
        $this->addSql('ALTER TABLE sustainability_metrics CHANGE co2_saved_kg co2_saved_kg NUMERIC(10, 0) NOT NULL, CHANGE cost_saved cost_saved NUMERIC(10, 0) DEFAULT NULL, CHANGE calculated_at calculated_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX uk_email_role ON user');
        $this->addSql('ALTER TABLE user ADD last_name VARCHAR(255) DEFAULT NULL, ADD banned TINYINT(1) DEFAULT 0 NOT NULL, CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE password_hash password_hash VARCHAR(255) NOT NULL, CHANGE role role VARCHAR(255) NOT NULL, CHANGE reference_id reference_id INT DEFAULT NULL, CHANGE phone phone VARCHAR(255) DEFAULT NULL, CHANGE full_name first_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('DROP INDEX email ON user1');
        $this->addSql('ALTER TABLE user1 CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE name name VARCHAR(255) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE role role VARCHAR(255) NOT NULL, CHANGE status status VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX idx_wasterecord_ingredient_date ON wasterecord');
        $this->addSql('ALTER TABLE wasterecord CHANGE quantityWasted quantityWasted NUMERIC(10, 0) NOT NULL');
        $this->addSql('ALTER TABLE wasterecord ADD CONSTRAINT FK_90F7A4D85B5CA8A5 FOREIGN KEY (ingredientId) REFERENCES ingredient (id)');
        $this->addSql('DROP INDEX ingredientid ON wasterecord');
        $this->addSql('CREATE INDEX IDX_90F7A4D85B5CA8A5 ON wasterecord (ingredientId)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE car (carId INT NOT NULL, deliveryManId INT NOT NULL, brand VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, model VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, licensePlate VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, year INT NOT NULL, color VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, status VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE notifications (notification_id INT AUTO_INCREMENT NOT NULL, user_id BIGINT NOT NULL, donation_event_id INT DEFAULT NULL, message VARCHAR(500) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, notification_type VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'BOTH\' COLLATE `utf8mb4_general_ci`, status VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'PENDING\' COLLATE `utf8mb4_general_ci`, scheduled_time DATETIME NOT NULL, sent_at DATETIME DEFAULT NULL, is_read TINYINT(1) DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX fk_notification_user (user_id), INDEX fk_notification_event (donation_event_id), INDEX idx_notification_status (status), INDEX idx_notification_scheduled (scheduled_time), PRIMARY KEY(notification_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE password_reset_token (id BIGINT AUTO_INCREMENT NOT NULL, user_id BIGINT NOT NULL, token VARCHAR(64) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, expires_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, used TINYINT(1) DEFAULT 0 NOT NULL, INDEX idx_prt_user (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE ratings (rating_id INT AUTO_INCREMENT NOT NULL, donation_event_id INT NOT NULL, user_id BIGINT NOT NULL, event_rating INT NOT NULL, food_rating INT NOT NULL, comment VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX fk_rating_event (donation_event_id), INDEX fk_rating_user (user_id), PRIMARY KEY(rating_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT fk_notification_event FOREIGN KEY (donation_event_id) REFERENCES food_donation_event (donation_event_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE password_reset_token ADD CONSTRAINT password_reset_token_ibfk_1 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ratings ADD CONSTRAINT fk_rating_event FOREIGN KEY (donation_event_id) REFERENCES food_donation_event (donation_event_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ratings ADD CONSTRAINT fk_rating_user FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        // $this->addSql('DROP TABLE restaurant_table');
        // $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE delivery DROP FOREIGN KEY FK_3781EC10FD128646');
        $this->addSql('ALTER TABLE delivery DROP FOREIGN KEY FK_3781EC1048CD51AF');
        $this->addSql('DROP INDEX UNIQ_3781EC10F5AA79D0 ON delivery');
        $this->addSql('DROP INDEX IDX_3781EC1048CD51AF ON delivery');
        $this->addSql('ALTER TABLE delivery DROP FOREIGN KEY FK_3781EC10FD128646');
        $this->addSql('ALTER TABLE delivery DROP fleet_car_id, DROP cart_items, DROP order_total, DROP restaurant_rating, DROP license_plate, CHANGE delivery_id delivery_id BIGINT AUTO_INCREMENT NOT NULL, CHANGE delivery_man_id delivery_man_id BIGINT DEFAULT NULL, CHANGE order_id order_id BIGINT NOT NULL, CHANGE recipient_name recipient_name VARCHAR(100) DEFAULT NULL, CHANGE recipient_phone recipient_phone VARCHAR(20) DEFAULT NULL, CHANGE status status VARCHAR(50) DEFAULT \'PENDING\', CHANGE actual_delivery_date actual_delivery_date DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE current_latitude current_latitude NUMERIC(10, 8) DEFAULT NULL, CHANGE current_longitude current_longitude NUMERIC(11, 8) DEFAULT NULL, CHANGE delivery_notes delivery_notes TEXT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE delivery ADD CONSTRAINT fk_delivery_man FOREIGN KEY (delivery_man_id) REFERENCES delivery_man (delivery_man_id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX uk_order_id ON delivery (order_id)');
        $this->addSql('CREATE INDEX idx_scheduled_date ON delivery (scheduled_date)');
        $this->addSql('CREATE INDEX idx_order_id ON delivery (order_id)');
        $this->addSql('CREATE INDEX idx_status ON delivery (status)');
        $this->addSql('DROP INDEX idx_3781ec10fd128646 ON delivery');
        $this->addSql('CREATE INDEX idx_delivery_man_id ON delivery (delivery_man_id)');
        $this->addSql('ALTER TABLE delivery ADD CONSTRAINT FK_3781EC10FD128646 FOREIGN KEY (delivery_man_id) REFERENCES delivery_man (delivery_man_id)');
        $this->addSql('ALTER TABLE delivery_man CHANGE delivery_man_id delivery_man_id BIGINT AUTO_INCREMENT NOT NULL, CHANGE name name VARCHAR(100) NOT NULL, CHANGE phone phone VARCHAR(20) NOT NULL, CHANGE email email VARCHAR(100) DEFAULT NULL, CHANGE vehicle_type vehicle_type VARCHAR(50) DEFAULT NULL, CHANGE vehicle_number vehicle_number VARCHAR(50) DEFAULT NULL, CHANGE status status VARCHAR(50) DEFAULT \'ACTIVE\', CHANGE salary salary NUMERIC(10, 2) DEFAULT NULL, CHANGE rating rating NUMERIC(3, 2) DEFAULT \'0.00\', CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('CREATE INDEX idx_status ON delivery_man (status)');
        $this->addSql('CREATE UNIQUE INDEX phone ON delivery_man (phone)');
        $this->addSql('CREATE INDEX idx_phone ON delivery_man (phone)');
        $this->addSql('CREATE UNIQUE INDEX email ON delivery_man (email)');
        $this->addSql('CREATE INDEX idx_vehicle_type ON delivery_man (vehicle_type)');
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
        $this->addSql('DROP INDEX IDX_77196056148EB0CB ON dish_ingredient');
        $this->addSql('ALTER TABLE dish_ingredient DROP FOREIGN KEY FK_77196056933FE08C');
        $this->addSql('ALTER TABLE dish_ingredient CHANGE quantity_required quantity_required DOUBLE PRECISION NOT NULL');
        $this->addSql('DROP INDEX idx_77196056933fe08c ON dish_ingredient');
        $this->addSql('CREATE INDEX idx_dish_ingredient_ingredient ON dish_ingredient (ingredient_id)');
        $this->addSql('ALTER TABLE dish_ingredient ADD CONSTRAINT FK_77196056933FE08C FOREIGN KEY (ingredient_id) REFERENCES ingredient (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fleet_car CHANGE car_id car_id BIGINT NOT NULL, CHANGE make make VARCHAR(128) DEFAULT \'\' NOT NULL, CHANGE model model VARCHAR(128) DEFAULT \'\' NOT NULL, CHANGE license_plate license_plate VARCHAR(64) DEFAULT \'\' NOT NULL, CHANGE vehicle_type vehicle_type VARCHAR(64) DEFAULT \'Sedan\' NOT NULL, CHANGE delivery_man_id delivery_man_id BIGINT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX uk_fleet_delivery_man ON fleet_car (delivery_man_id)');
        $this->addSql('ALTER TABLE food_donation_event CHANGE charity_name charity_name VARCHAR(100) NOT NULL, CHANGE status status VARCHAR(50) DEFAULT \'PENDING\', CHANGE delivery_id delivery_id BIGINT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE food_donation_event ADD CONSTRAINT fk_event_delivery FOREIGN KEY (delivery_id) REFERENCES delivery (delivery_id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE food_donation_event ADD CONSTRAINT fk_events_delivery FOREIGN KEY (delivery_id) REFERENCES delivery (delivery_id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX idx_delivery_id ON food_donation_event (delivery_id)');
        $this->addSql('CREATE INDEX idx_event_date ON food_donation_event (event_date)');
        $this->addSql('CREATE INDEX idx_status ON food_donation_event (status)');
        $this->addSql('ALTER TABLE food_donation_items MODIFY donation_event_id INT NOT NULL');
        $this->addSql('DROP INDEX `PRIMARY` ON food_donation_items');
        $this->addSql('ALTER TABLE food_donation_items CHANGE donation_event_id donation_event_id INT NOT NULL');
        $this->addSql('ALTER TABLE food_donation_items ADD CONSTRAINT fk_items_event FOREIGN KEY (donation_event_id) REFERENCES food_donation_event (donation_event_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE food_donation_items ADD CONSTRAINT fk_items_dish FOREIGN KEY (item_id) REFERENCES dish (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX idx_item_id ON food_donation_items (item_id)');
        $this->addSql('CREATE INDEX IDX_54E57C7BBABCF7FB ON food_donation_items (donation_event_id)');
        $this->addSql('ALTER TABLE food_donation_items ADD PRIMARY KEY (donation_event_id, item_id)');
        $this->addSql('ALTER TABLE ingredient CHANGE quantityInStock quantityInStock DOUBLE PRECISION NOT NULL, CHANGE unit unit VARCHAR(50) NOT NULL, CHANGE minStockLevel minStockLevel DOUBLE PRECISION NOT NULL, CHANGE unitCost unitCost NUMERIC(10, 2) NOT NULL');
        $this->addSql('CREATE INDEX idx_ingredient_expiry_stock ON ingredient (expiryDate, quantityInStock)');
        $this->addSql('ALTER TABLE menu ADD isActive TINYINT(1) DEFAULT 1 NOT NULL, DROP is_active, CHANGE title title VARCHAR(120) NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE sustainability_metrics CHANGE co2_saved_kg co2_saved_kg NUMERIC(10, 2) NOT NULL, CHANGE cost_saved cost_saved NUMERIC(12, 2) DEFAULT NULL, CHANGE calculated_at calculated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE sustainability_metrics ADD CONSTRAINT fk_metrics_event FOREIGN KEY (donation_event_id) REFERENCES food_donation_event (donation_event_id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX idx_donation_event_id ON sustainability_metrics (donation_event_id)');
        $this->addSql('ALTER TABLE user ADD full_name VARCHAR(255) DEFAULT NULL, DROP first_name, DROP last_name, DROP banned, CHANGE id id BIGINT AUTO_INCREMENT NOT NULL, CHANGE password_hash password_hash VARCHAR(512) NOT NULL, CHANGE role role VARCHAR(32) NOT NULL, CHANGE reference_id reference_id BIGINT DEFAULT NULL, CHANGE phone phone VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX uk_email_role ON user (email, role)');
        $this->addSql('ALTER TABLE user1 CHANGE id id INT NOT NULL, CHANGE name name VARCHAR(100) NOT NULL, CHANGE email email VARCHAR(150) NOT NULL, CHANGE role role VARCHAR(20) NOT NULL, CHANGE status status VARCHAR(20) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX email ON user1 (email)');
        $this->addSql('ALTER TABLE wasterecord DROP FOREIGN KEY FK_90F7A4D85B5CA8A5');
        $this->addSql('ALTER TABLE wasterecord DROP FOREIGN KEY FK_90F7A4D85B5CA8A5');
        $this->addSql('ALTER TABLE wasterecord CHANGE quantityWasted quantityWasted DOUBLE PRECISION NOT NULL');
        $this->addSql('CREATE INDEX idx_wasterecord_ingredient_date ON wasterecord (ingredientId, date)');
        $this->addSql('DROP INDEX idx_90f7a4d85b5ca8a5 ON wasterecord');
        $this->addSql('CREATE INDEX ingredientId ON wasterecord (ingredientId)');
        $this->addSql('ALTER TABLE wasterecord ADD CONSTRAINT FK_90F7A4D85B5CA8A5 FOREIGN KEY (ingredientId) REFERENCES ingredient (id)');
    }
}
