<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260416120916 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book DROP FOREIGN KEY FK_CBE5A331F675F31B');
        $this->addSql('DROP TABLE author');
        $this->addSql('DROP TABLE book');
        $this->addSql('ALTER TABLE delivery DROP FOREIGN KEY fk_delivery_man');
        $this->addSql('DROP INDEX idx_status ON delivery');
        $this->addSql('DROP INDEX uk_order_id ON delivery');
        $this->addSql('DROP INDEX idx_scheduled_date ON delivery');
        $this->addSql('DROP INDEX idx_order_id ON delivery');
        $this->addSql('ALTER TABLE delivery DROP FOREIGN KEY fk_delivery_man');
        $this->addSql('ALTER TABLE delivery ADD restaurant_rating INT DEFAULT NULL, CHANGE delivery_id delivery_id INT AUTO_INCREMENT NOT NULL, CHANGE delivery_man_id delivery_man_id INT DEFAULT NULL, CHANGE order_id order_id INT NOT NULL, CHANGE recipient_name recipient_name VARCHAR(255) DEFAULT NULL, CHANGE recipient_phone recipient_phone VARCHAR(255) DEFAULT NULL, CHANGE status status VARCHAR(255) DEFAULT NULL, CHANGE actual_delivery_date actual_delivery_date DATETIME DEFAULT NULL, CHANGE current_latitude current_latitude NUMERIC(10, 0) DEFAULT NULL, CHANGE current_longitude current_longitude NUMERIC(10, 0) DEFAULT NULL, CHANGE delivery_notes delivery_notes LONGTEXT DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE delivery ADD CONSTRAINT FK_3781EC10FD128646 FOREIGN KEY (delivery_man_id) REFERENCES delivery_man (delivery_man_id)');
        $this->addSql('ALTER TABLE delivery ADD CONSTRAINT FK_3781EC1048CD51AF FOREIGN KEY (fleet_car_id) REFERENCES fleet_car (car_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3781EC10F5AA79D0 ON delivery (license_plate)');
        $this->addSql('CREATE INDEX IDX_3781EC1048CD51AF ON delivery (fleet_car_id)');
        $this->addSql('DROP INDEX idx_delivery_man_id ON delivery');
        $this->addSql('CREATE INDEX IDX_3781EC10FD128646 ON delivery (delivery_man_id)');
        $this->addSql('ALTER TABLE delivery ADD CONSTRAINT fk_delivery_man FOREIGN KEY (delivery_man_id) REFERENCES delivery_man (delivery_man_id) ON DELETE SET NULL');
        $this->addSql('DROP INDEX idx_vehicle_type ON delivery_man');
        $this->addSql('DROP INDEX vehicle_number ON delivery_man');
        $this->addSql('DROP INDEX idx_status ON delivery_man');
        $this->addSql('DROP INDEX phone ON delivery_man');
        $this->addSql('DROP INDEX idx_phone ON delivery_man');
        $this->addSql('DROP INDEX email ON delivery_man');
        $this->addSql('ALTER TABLE delivery_man CHANGE delivery_man_id delivery_man_id INT AUTO_INCREMENT NOT NULL, CHANGE name name VARCHAR(255) NOT NULL, CHANGE phone phone VARCHAR(255) NOT NULL, CHANGE email email VARCHAR(255) DEFAULT NULL, CHANGE vehicle_type vehicle_type VARCHAR(255) DEFAULT NULL, CHANGE vehicle_number vehicle_number VARCHAR(255) DEFAULT NULL, CHANGE status status VARCHAR(255) DEFAULT NULL, CHANGE salary salary NUMERIC(10, 0) DEFAULT NULL, CHANGE rating rating NUMERIC(10, 0) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE dish DROP FOREIGN KEY fk_dish_menu');
        $this->addSql('ALTER TABLE dish DROP FOREIGN KEY fk_dish_menu');
        $this->addSql('ALTER TABLE dish CHANGE menu_id menu_id INT DEFAULT NULL, CHANGE name name VARCHAR(255) NOT NULL, CHANGE base_price base_price NUMERIC(10, 0) NOT NULL, CHANGE available available TINYINT(1) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE dish ADD CONSTRAINT FK_957D8CB8CCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (id)');
        $this->addSql('DROP INDEX fk_dish_menu ON dish');
        $this->addSql('CREATE INDEX IDX_957D8CB8CCD7E912 ON dish (menu_id)');
        $this->addSql('ALTER TABLE dish ADD CONSTRAINT fk_dish_menu FOREIGN KEY (menu_id) REFERENCES menu (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dish_ingredient CHANGE quantity_required quantity_required NUMERIC(10, 0) NOT NULL, ADD PRIMARY KEY (dish_id, ingredient_id)');
        $this->addSql('ALTER TABLE dish_ingredient ADD CONSTRAINT FK_77196056148EB0CB FOREIGN KEY (dish_id) REFERENCES dish (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dish_ingredient ADD CONSTRAINT FK_77196056933FE08C FOREIGN KEY (ingredient_id) REFERENCES ingredient (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_77196056148EB0CB ON dish_ingredient (dish_id)');
        $this->addSql('CREATE INDEX IDX_77196056933FE08C ON dish_ingredient (ingredient_id)');
        $this->addSql('DROP INDEX idx_status ON food_donation_event');
        $this->addSql('DROP INDEX idx_delivery_id ON food_donation_event');
        $this->addSql('DROP INDEX idx_event_date ON food_donation_event');
        $this->addSql('ALTER TABLE food_donation_event CHANGE charity_name charity_name VARCHAR(255) NOT NULL, CHANGE status status VARCHAR(50) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX idx_item_id ON food_donation_items');
        $this->addSql('ALTER TABLE ingredient CHANGE quantityInStock quantityInStock NUMERIC(10, 0) NOT NULL, CHANGE unit unit VARCHAR(255) NOT NULL, CHANGE minStockLevel minStockLevel NUMERIC(10, 0) NOT NULL, CHANGE unitCost unitCost NUMERIC(10, 0) NOT NULL');
        $this->addSql('ALTER TABLE menu DROP is_active, CHANGE title title VARCHAR(255) NOT NULL, CHANGE isActive isActive TINYINT(1) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY fk_ord_reservation');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY fk_ord_client');
        $this->addSql('DROP INDEX client_id ON orders');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY fk_ord_reservation');
        $this->addSql('ALTER TABLE orders ADD cart_items LONGTEXT DEFAULT NULL, CHANGE order_type order_type VARCHAR(20) NOT NULL, CHANGE status status VARCHAR(20) DEFAULT NULL');
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
        $this->addSql('ALTER TABLE user ADD last_name VARCHAR(255) DEFAULT NULL, ADD banned TINYINT(1) DEFAULT 0 NOT NULL, CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE password_hash password_hash VARCHAR(255) NOT NULL, CHANGE role role VARCHAR(255) NOT NULL, CHANGE reference_id reference_id INT DEFAULT NULL, CHANGE phone phone VARCHAR(255) DEFAULT NULL, CHANGE full_name first_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('DROP INDEX email ON user1');
        $this->addSql('ALTER TABLE user1 CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE name name VARCHAR(255) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE role role VARCHAR(255) NOT NULL, CHANGE status status VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE wasterecord DROP FOREIGN KEY FK_8A12AA31EBA5B6D');
        $this->addSql('ALTER TABLE wasterecord CHANGE quantityWasted quantityWasted NUMERIC(10, 0) NOT NULL');
        $this->addSql('DROP INDEX idx_8a12aa31eba5b6d ON wasterecord');
        $this->addSql('CREATE INDEX IDX_90F7A4D85B5CA8A5 ON wasterecord (ingredientId)');
        $this->addSql('ALTER TABLE wasterecord ADD CONSTRAINT FK_8A12AA31EBA5B6D FOREIGN KEY (ingredientId) REFERENCES ingredient (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE author (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(55) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, email VARCHAR(55) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE book (id INT AUTO_INCREMENT NOT NULL, author_id INT NOT NULL, title VARCHAR(55) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, publication_date DATE NOT NULL, enabled TINYINT(1) NOT NULL, INDEX IDX_CBE5A331F675F31B (author_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE book ADD CONSTRAINT FK_CBE5A331F675F31B FOREIGN KEY (author_id) REFERENCES author (id)');
        $this->addSql('ALTER TABLE delivery DROP FOREIGN KEY FK_3781EC10FD128646');
        $this->addSql('ALTER TABLE delivery DROP FOREIGN KEY FK_3781EC1048CD51AF');
        $this->addSql('DROP INDEX UNIQ_3781EC10F5AA79D0 ON delivery');
        $this->addSql('DROP INDEX IDX_3781EC1048CD51AF ON delivery');
        $this->addSql('ALTER TABLE delivery DROP FOREIGN KEY FK_3781EC10FD128646');
        $this->addSql('ALTER TABLE delivery DROP restaurant_rating, CHANGE delivery_id delivery_id BIGINT AUTO_INCREMENT NOT NULL, CHANGE delivery_man_id delivery_man_id BIGINT DEFAULT NULL, CHANGE order_id order_id BIGINT NOT NULL, CHANGE recipient_name recipient_name VARCHAR(100) DEFAULT NULL, CHANGE recipient_phone recipient_phone VARCHAR(20) DEFAULT NULL, CHANGE status status VARCHAR(50) DEFAULT \'PENDING\', CHANGE actual_delivery_date actual_delivery_date DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE current_latitude current_latitude NUMERIC(10, 8) DEFAULT NULL, CHANGE current_longitude current_longitude NUMERIC(11, 8) DEFAULT NULL, CHANGE delivery_notes delivery_notes TEXT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE delivery ADD CONSTRAINT fk_delivery_man FOREIGN KEY (delivery_man_id) REFERENCES delivery_man (delivery_man_id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX idx_status ON delivery (status)');
        $this->addSql('CREATE UNIQUE INDEX uk_order_id ON delivery (order_id)');
        $this->addSql('CREATE INDEX idx_scheduled_date ON delivery (scheduled_date)');
        $this->addSql('CREATE INDEX idx_order_id ON delivery (order_id)');
        $this->addSql('DROP INDEX idx_3781ec10fd128646 ON delivery');
        $this->addSql('CREATE INDEX idx_delivery_man_id ON delivery (delivery_man_id)');
        $this->addSql('ALTER TABLE delivery ADD CONSTRAINT FK_3781EC10FD128646 FOREIGN KEY (delivery_man_id) REFERENCES delivery_man (delivery_man_id)');
        $this->addSql('ALTER TABLE delivery_man CHANGE delivery_man_id delivery_man_id BIGINT AUTO_INCREMENT NOT NULL, CHANGE name name VARCHAR(100) NOT NULL, CHANGE phone phone VARCHAR(20) NOT NULL, CHANGE email email VARCHAR(100) DEFAULT NULL, CHANGE vehicle_type vehicle_type VARCHAR(50) DEFAULT NULL, CHANGE vehicle_number vehicle_number VARCHAR(50) DEFAULT NULL, CHANGE status status VARCHAR(50) DEFAULT \'ACTIVE\', CHANGE salary salary NUMERIC(10, 2) DEFAULT NULL, CHANGE rating rating NUMERIC(3, 2) DEFAULT \'0.00\', CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('CREATE INDEX idx_vehicle_type ON delivery_man (vehicle_type)');
        $this->addSql('CREATE UNIQUE INDEX vehicle_number ON delivery_man (vehicle_number)');
        $this->addSql('CREATE INDEX idx_status ON delivery_man (status)');
        $this->addSql('CREATE UNIQUE INDEX phone ON delivery_man (phone)');
        $this->addSql('CREATE INDEX idx_phone ON delivery_man (phone)');
        $this->addSql('CREATE UNIQUE INDEX email ON delivery_man (email)');
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
        $this->addSql('DROP INDEX IDX_77196056933FE08C ON dish_ingredient');
        $this->addSql('DROP INDEX `primary` ON dish_ingredient');
        $this->addSql('ALTER TABLE dish_ingredient CHANGE quantity_required quantity_required DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE food_donation_event CHANGE charity_name charity_name VARCHAR(100) NOT NULL, CHANGE status status VARCHAR(50) DEFAULT \'PENDING\', CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('CREATE INDEX idx_status ON food_donation_event (status)');
        $this->addSql('CREATE INDEX idx_delivery_id ON food_donation_event (delivery_id)');
        $this->addSql('CREATE INDEX idx_event_date ON food_donation_event (event_date)');
        $this->addSql('CREATE INDEX idx_item_id ON food_donation_items (item_id)');
        $this->addSql('ALTER TABLE ingredient CHANGE quantityInStock quantityInStock DOUBLE PRECISION NOT NULL, CHANGE unit unit VARCHAR(50) NOT NULL, CHANGE minStockLevel minStockLevel DOUBLE PRECISION NOT NULL, CHANGE unitCost unitCost NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE menu ADD is_active TINYINT(1) DEFAULT 1 NOT NULL, CHANGE title title VARCHAR(120) NOT NULL, CHANGE isActive isActive TINYINT(1) DEFAULT 1 NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEEB83297E7');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEEB83297E7');
        $this->addSql('ALTER TABLE orders DROP cart_items, CHANGE order_type order_type VARCHAR(255) NOT NULL, CHANGE status status VARCHAR(255) DEFAULT NULL');
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
        $this->addSql('ALTER TABLE user ADD full_name VARCHAR(255) DEFAULT NULL, DROP first_name, DROP last_name, DROP banned, CHANGE id id BIGINT AUTO_INCREMENT NOT NULL, CHANGE password_hash password_hash VARCHAR(512) NOT NULL, CHANGE role role VARCHAR(32) NOT NULL, CHANGE reference_id reference_id BIGINT DEFAULT NULL, CHANGE phone phone VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX uk_email_role ON user (email, role)');
        $this->addSql('ALTER TABLE user1 CHANGE id id INT NOT NULL, CHANGE name name VARCHAR(100) NOT NULL, CHANGE email email VARCHAR(150) NOT NULL, CHANGE role role VARCHAR(20) NOT NULL, CHANGE status status VARCHAR(20) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX email ON user1 (email)');
        $this->addSql('ALTER TABLE wasterecord DROP FOREIGN KEY FK_90F7A4D85B5CA8A5');
        $this->addSql('ALTER TABLE wasterecord CHANGE quantityWasted quantityWasted NUMERIC(10, 2) NOT NULL');
        $this->addSql('DROP INDEX idx_90f7a4d85b5ca8a5 ON wasterecord');
        $this->addSql('CREATE INDEX IDX_8A12AA31EBA5B6D ON wasterecord (ingredientId)');
        $this->addSql('ALTER TABLE wasterecord ADD CONSTRAINT FK_90F7A4D85B5CA8A5 FOREIGN KEY (ingredientId) REFERENCES ingredient (id)');
    }
}
