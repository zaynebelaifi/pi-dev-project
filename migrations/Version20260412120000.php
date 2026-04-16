<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260412120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create orders table and ensure cart_items column exists';
    }

    public function up(Schema $schema): void
    {
        // Create table if it does not exist (includes cart_items)
        $this->addSql("CREATE TABLE IF NOT EXISTS `orders` (
            `order_id` INT(11) NOT NULL AUTO_INCREMENT,
            `client_id` INT(11) NOT NULL,
            `reservation_id` INT(11) DEFAULT NULL,
            `order_type` ENUM('DINE_IN','DELIVERY') NOT NULL,
            `order_date` DATETIME NOT NULL,
            `delivery_address` VARCHAR(200) DEFAULT NULL,
            `status` ENUM('PENDING','PREPARED','DELIVERED') DEFAULT NULL,
            `total_amount` DECIMAL(10,2) NOT NULL,
            `cart_items` LONGTEXT DEFAULT NULL,
            PRIMARY KEY(order_id),
            KEY client_id (client_id),
            KEY reservation_id (reservation_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        // Ensure cart_items column exists for environments where the table was created without it
        $this->addSql("ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `cart_items` LONGTEXT DEFAULT NULL AFTER `total_amount`");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS `orders`');
    }
}
