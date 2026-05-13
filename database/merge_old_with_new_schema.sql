-- Merged SQL schema (project 11 base + attributes from project 10) with fake data
-- Generated on 2026-05-11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

CREATE TABLE `assignment_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `car_id` bigint(20) NOT NULL,
  `delivery_man_id` bigint(20) NOT NULL,
  `assigned_by_id` bigint(20) DEFAULT NULL,
  `assigned_at` datetime NOT NULL,
  `unassigned_at` datetime DEFAULT NULL,
  `reason` varchar(40) NOT NULL DEFAULT 'manual',
  `status` varchar(30) NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `IDX_ASSIGNMENT_HISTORY_CAR_ID` (`car_id`),
  KEY `IDX_ASSIGNMENT_HISTORY_DELIVERY_MAN_ID` (`delivery_man_id`),
  KEY `IDX_ASSIGNMENT_HISTORY_ASSIGNED_BY_ID` (`assigned_by_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `actor_id` bigint(20) DEFAULT NULL,
  `action` varchar(30) NOT NULL,
  `entity_type` varchar(60) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`changes`)),
  `timestamp` datetime NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_AUDIT_LOG_ACTOR_ID` (`actor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `author` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(55) DEFAULT NULL,
  `email` varchar(55) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `delivery` (
  `delivery_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) NOT NULL,
  `delivery_man_id` bigint(20) DEFAULT NULL,
  `delivery_address` varchar(255) NOT NULL,
  `recipient_name` varchar(255) DEFAULT NULL,
  `recipient_phone` varchar(255) DEFAULT NULL,
  `pickup_location` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'PENDING',
  `scheduled_date` timestamp NULL DEFAULT NULL,
  `actual_delivery_date` datetime DEFAULT NULL,
  `estimated_time` int(11) DEFAULT NULL,
  `current_latitude` decimal(10,8) DEFAULT NULL,
  `current_longitude` decimal(11,8) DEFAULT NULL,
  `delivery_notes` text DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(255) DEFAULT NULL,
  `updated_by` varchar(255) DEFAULT NULL,
  `cart_items` longtext DEFAULT NULL,
  `order_total` decimal(10,2) DEFAULT NULL,
  `fleet_car_id` bigint(20) DEFAULT NULL,
  `license_plate` varchar(255) DEFAULT NULL,
  `restaurant_rating` int(11) DEFAULT NULL,
  `destination_latitude` decimal(10,6) DEFAULT NULL,
  `destination_longitude` decimal(10,6) DEFAULT NULL,
  `driver_latitude` decimal(10,6) DEFAULT NULL,
  `driver_longitude` decimal(10,6) DEFAULT NULL,
  `destination_lat` decimal(10,7) DEFAULT NULL,
  `destination_lng` decimal(10,7) DEFAULT NULL,
  `candidate_delivery_men` longtext DEFAULT NULL,
  `candidate_index` int(11) DEFAULT NULL,
  PRIMARY KEY (`delivery_id`),
  KEY `idx_delivery_man_id` (`delivery_man_id`),
  KEY `IDX_DELIVERY_FLEET_CAR` (`fleet_car_id`),
  KEY `IDX_DELIVERY_CREATED_BY` (`created_by`),
  KEY `IDX_DELIVERY_UPDATED_BY` (`updated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `delivery_feature` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `delivery_id` int(11) NOT NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '(DC2Type:json)' CHECK (json_valid(`features`)),
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `delivery_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` varchar(64) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `review_text` longtext NOT NULL,
  `rating` int DEFAULT NULL,
  `sentiment` varchar(16) DEFAULT NULL,
  `confidence` double DEFAULT NULL,
  `summary` varchar(255) DEFAULT NULL,
  `routed_to` varchar(32) DEFAULT NULL,
  `support_ticket` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `delivery_man` (
  `delivery_man_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `vehicle_type` varchar(50) DEFAULT NULL,
  `vehicle_number` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'ACTIVE',
  `address` varchar(255) DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `date_of_joining` date DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(255) DEFAULT NULL,
  `updated_by` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,6) DEFAULT NULL,
  `longitude` decimal(10,6) DEFAULT NULL,
  `last_location_update` datetime DEFAULT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `license_expiry_date` date DEFAULT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `current_car_id` bigint(20) DEFAULT NULL,
  `average_rating` double DEFAULT NULL,
  `total_deliveries` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`delivery_man_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `dish` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `available` tinyint(1) NOT NULL DEFAULT 1,
  `stock_quantity` int(11) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `dish_ingredient` (
  `dish_id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `quantity_required` double NOT NULL,
  PRIMARY KEY (`dish_id`, `ingredient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `donation_event_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `event_ratings` (
  `rating_id` int(11) NOT NULL AUTO_INCREMENT,
  `donation_event_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `event_rating` int(11) DEFAULT NULL,
  `food_rating` int(11) DEFAULT NULL,
  `comment` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`rating_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `event_registration` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `donation_event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fleet_car` (
  `car_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `make` varchar(128) NOT NULL DEFAULT '',
  `model` varchar(128) NOT NULL DEFAULT '',
  `license_plate` varchar(64) NOT NULL DEFAULT '',
  `vehicle_type` varchar(64) NOT NULL DEFAULT 'Sedan',
  `delivery_man_id` bigint(20) DEFAULT NULL,
  `color` varchar(255) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `fuel_type` varchar(20) DEFAULT NULL,
  `mileage` int(11) DEFAULT NULL,
  `registration_date` date DEFAULT NULL,
  `last_maintenance_date` date DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'AVAILABLE',
  `latitude` decimal(10,6) DEFAULT NULL,
  `longitude` decimal(10,6) DEFAULT NULL,
  `last_update` datetime DEFAULT NULL,
  `battery_level` int(11) DEFAULT NULL,
  `fuel_level` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`car_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `food_donation_event` (
  `donation_event_id` int(11) NOT NULL AUTO_INCREMENT,
  `event_date` datetime NOT NULL,
  `total_quantity` int(11) NOT NULL,
  `charity_name` varchar(100) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Scheduled',
  `delivery_id` bigint(20) DEFAULT NULL,
  `calendar_event_id` varchar(255) DEFAULT NULL,
  `sms_reminder_sent` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reminder_sent_at` datetime DEFAULT NULL,
  PRIMARY KEY (`donation_event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `food_donation_items` (
  `donation_event_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  PRIMARY KEY (`donation_event_id`, `item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `gps_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `car_id` bigint(20) NOT NULL,
  `delivery_man_id` bigint(20) DEFAULT NULL,
  `latitude` decimal(10,6) NOT NULL,
  `longitude` decimal(10,6) NOT NULL,
  `accuracy` int(11) DEFAULT NULL,
  `altitude` double DEFAULT NULL,
  `speed` double DEFAULT NULL,
  `bearing` double DEFAULT NULL,
  `timestamp` datetime NOT NULL,
  `source` varchar(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ingredient` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `quantityInStock` double NOT NULL,
  `unit` varchar(50) NOT NULL,
  `createdAt` datetime DEFAULT NULL,
  `minStockLevel` double NOT NULL,
  `unitCost` decimal(10,2) NOT NULL,
  `expiryDate` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `isActive` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `messenger_messages` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `body` longtext NOT NULL,
  `headers` longtext NOT NULL,
  `queue_name` varchar(190) NOT NULL,
  `created_at` datetime NOT NULL,
  `available_at` datetime NOT NULL,
  `delivered_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_id` bigint(20) NOT NULL,
  `type` varchar(30) NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` longtext NOT NULL,
  `related_entity` varchar(60) DEFAULT NULL,
  `related_entity_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_NOTIFICATION_RECIPIENT` (`recipient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `donation_event_id` int(11) DEFAULT NULL,
  `message` varchar(500) NOT NULL,
  `notification_type` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `scheduled_time` datetime NOT NULL,
  `sent_at` datetime DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`notification_id`)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `reservation_id` int(11) DEFAULT NULL,
  `client_id` int(11) NOT NULL,
  `order_type` varchar(20) NOT NULL,
  `order_date` datetime NOT NULL,
  `delivery_address` varchar(200) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `cart_items` longtext DEFAULT NULL,
  `payment_method` text DEFAULT NULL,
  PRIMARY KEY (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_token` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ratings` (
  `rating_id` int(11) NOT NULL AUTO_INCREMENT,
  `donation_event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_rating` int(11) DEFAULT NULL,
  `food_rating` int(11) DEFAULT NULL,
  `comment` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`rating_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `reservation` (
  `reservation_id` int(11) NOT NULL AUTO_INCREMENT,
  `table_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `reservation_date` date NOT NULL,
  `reservation_time` time NOT NULL,
  `number_of_guests` int(11) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'CONFIRMED',
  PRIMARY KEY (`reservation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `restaurant_table` (
  `table_id` int(11) NOT NULL AUTO_INCREMENT,
  `capacity` int(11) NOT NULL,
  `status` varchar(255) NOT NULL,
  PRIMARY KEY (`table_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sustainability_metrics` (
  `metric_id` int(11) NOT NULL AUTO_INCREMENT,
  `donation_event_id` int(11) NOT NULL,
  `total_quantity` int(11) NOT NULL,
  `meals_provided` int(11) NOT NULL,
  `co2_saved_kg` decimal(10,2) NOT NULL,
  `cost_saved` decimal(12,2) DEFAULT NULL,
  `calculated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`metric_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `user` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(512) NOT NULL,
  `role` varchar(32) NOT NULL,
  `reference_id` bigint(20) DEFAULT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `phone` varchar(64) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `banned` tinyint(1) NOT NULL DEFAULT 0,
  `phone_number` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `profile_image` varchar(255) DEFAULT NULL,
  `remember_token` varchar(32) DEFAULT NULL,
  `remember_token_expiry` datetime DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `password_changed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `user1` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL,
  `status` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `wasterecord` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ingredientId` int(11) NOT NULL,
  `quantityWasted` double NOT NULL,
  `wasteType` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `reason` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `webauthn_credential` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `credential_id` varchar(512) NOT NULL,
  `user_handle` varchar(128) NOT NULL,
  `source_json` longtext NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `public_key` longtext DEFAULT NULL,
  `counter` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Foreign keys
ALTER TABLE `assignment_history`
  ADD CONSTRAINT `FK_ASSIGNMENT_HISTORY_ASSIGNED_BY` FOREIGN KEY (`assigned_by_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `FK_ASSIGNMENT_HISTORY_CAR` FOREIGN KEY (`car_id`) REFERENCES `fleet_car` (`car_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_ASSIGNMENT_HISTORY_DELIVERY_MAN` FOREIGN KEY (`delivery_man_id`) REFERENCES `delivery_man` (`delivery_man_id`) ON DELETE CASCADE;

ALTER TABLE `audit_log`
  ADD CONSTRAINT `FK_AUDIT_LOG_ACTOR` FOREIGN KEY (`actor_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;

ALTER TABLE `delivery`
  ADD CONSTRAINT `FK_DELIVERY_FLEET_CAR` FOREIGN KEY (`fleet_car_id`) REFERENCES `fleet_car` (`car_id`),
  ADD CONSTRAINT `FK_DELIVERY_MAN` FOREIGN KEY (`delivery_man_id`) REFERENCES `delivery_man` (`delivery_man_id`);

ALTER TABLE `delivery_man`
  ADD CONSTRAINT `FK_DELIVERY_MAN_CURRENT_CAR` FOREIGN KEY (`current_car_id`) REFERENCES `fleet_car` (`car_id`) ON DELETE SET NULL;

ALTER TABLE `dish`
  ADD CONSTRAINT `FK_DISH_MENU` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `dish_ingredient`
  ADD CONSTRAINT `FK_DISH_INGREDIENT_DISH` FOREIGN KEY (`dish_id`) REFERENCES `dish` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_DISH_INGREDIENT_INGREDIENT` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredient` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `donation_event_item`
  ADD CONSTRAINT `FK_DONATION_EVENT_ITEM_DISH` FOREIGN KEY (`item_id`) REFERENCES `dish` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_DONATION_EVENT_ITEM_EVENT` FOREIGN KEY (`event_id`) REFERENCES `food_donation_event` (`donation_event_id`) ON DELETE CASCADE;

ALTER TABLE `event_ratings`
  ADD CONSTRAINT `FK_EVENT_RATINGS_EVENT` FOREIGN KEY (`donation_event_id`) REFERENCES `food_donation_event` (`donation_event_id`) ON DELETE CASCADE;

ALTER TABLE `event_registration`
  ADD CONSTRAINT `FK_EVENT_REGISTRATION_EVENT` FOREIGN KEY (`donation_event_id`) REFERENCES `food_donation_event` (`donation_event_id`) ON DELETE CASCADE;

ALTER TABLE `fleet_car`
  ADD CONSTRAINT `FK_FLEET_CAR_DELIVERY_MAN` FOREIGN KEY (`delivery_man_id`) REFERENCES `delivery_man` (`delivery_man_id`) ON DELETE SET NULL;

ALTER TABLE `food_donation_items`
  ADD CONSTRAINT `FK_FOOD_DONATION_ITEMS_DISH` FOREIGN KEY (`item_id`) REFERENCES `dish` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_FOOD_DONATION_ITEMS_EVENT` FOREIGN KEY (`donation_event_id`) REFERENCES `food_donation_event` (`donation_event_id`) ON DELETE CASCADE;

ALTER TABLE `gps_log`
  ADD CONSTRAINT `FK_GPS_LOG_CAR` FOREIGN KEY (`car_id`) REFERENCES `fleet_car` (`car_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_GPS_LOG_DELIVERY_MAN` FOREIGN KEY (`delivery_man_id`) REFERENCES `delivery_man` (`delivery_man_id`) ON DELETE SET NULL;

ALTER TABLE `notification`
  ADD CONSTRAINT `FK_NOTIFICATION_RECIPIENT` FOREIGN KEY (`recipient_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

ALTER TABLE `orders`
  ADD CONSTRAINT `FK_ORDERS_RESERVATION` FOREIGN KEY (`reservation_id`) REFERENCES `reservation` (`reservation_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_ORDERS_CLIENT` FOREIGN KEY (`client_id`) REFERENCES `user1` (`id`) ON UPDATE CASCADE;

ALTER TABLE `password_reset_token`
  ADD CONSTRAINT `FK_PASSWORD_RESET_TOKEN_USER` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

ALTER TABLE `ratings`
  ADD CONSTRAINT `FK_RATINGS_EVENT` FOREIGN KEY (`donation_event_id`) REFERENCES `food_donation_event` (`donation_event_id`) ON DELETE CASCADE;

ALTER TABLE `reservation`
  ADD CONSTRAINT `FK_RESERVATION_TABLE` FOREIGN KEY (`table_id`) REFERENCES `restaurant_table` (`table_id`),
  ADD CONSTRAINT `FK_RESERVATION_CLIENT` FOREIGN KEY (`client_id`) REFERENCES `user1` (`id`) ON UPDATE CASCADE;

ALTER TABLE `sustainability_metrics`
  ADD CONSTRAINT `FK_SUSTAINABILITY_EVENT` FOREIGN KEY (`donation_event_id`) REFERENCES `food_donation_event` (`donation_event_id`) ON DELETE CASCADE;

ALTER TABLE `wasterecord`
  ADD CONSTRAINT `FK_WASTERECORD_INGREDIENT` FOREIGN KEY (`ingredientId`) REFERENCES `ingredient` (`id`) ON DELETE CASCADE;

ALTER TABLE `webauthn_credential`
  ADD CONSTRAINT `FK_WEBAUTHN_USER` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;

-- Fake data (minimal set per table)
INSERT INTO `user` (`id`, `email`, `password_hash`, `role`, `reference_id`, `full_name`, `phone`, `address`, `first_name`, `last_name`, `banned`, `phone_number`, `is_active`, `is_verified`, `profile_image`, `remember_token`, `remember_token_expiry`, `reset_token`, `reset_token_expires_at`, `password_changed_at`, `created_at`, `updated_at`) VALUES
(1, 'admin@big4.com', '$2y$12$FAKEHASHadmin', 'ROLE_ADMIN', NULL, 'Admin User', '12345678', 'HQ', 'Admin', 'User', 0, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-11 10:00:00', '2026-05-11 10:00:00'),
(2, 'client@big4.com', '$2y$12$FAKEHASHclient', 'ROLE_CLIENT', NULL, 'Client One', '22222222', 'City Center', 'Client', 'One', 0, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-11 10:00:00', '2026-05-11 10:00:00'),
(3, 'driver@big4.com', '$2y$12$FAKEHASHdriver', 'ROLE_DELIVERY_MAN', 1, 'Driver One', '33333333', 'City Center', 'Driver', 'One', 0, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-11 10:00:00', '2026-05-11 10:00:00');

INSERT INTO `user1` (`id`, `name`, `email`, `password`, `role`, `status`) VALUES
(1, 'Customer One', 'customer@big4.com', '$2y$12$FAKEHASHcust', 'ROLE_CLIENT', 'ACTIVE');

INSERT INTO `author` (`id`, `username`, `email`) VALUES
(1, 'zayneb', 'zayneb@example.com');

INSERT INTO `delivery_man` (`delivery_man_id`, `name`, `phone`, `email`, `vehicle_type`, `vehicle_number`, `status`, `address`, `salary`, `date_of_joining`, `rating`, `created_at`, `updated_at`, `created_by`, `updated_by`, `latitude`, `longitude`, `last_location_update`, `license_number`, `license_expiry_date`, `is_available`, `current_car_id`, `average_rating`, `total_deliveries`) VALUES
(1, 'Samir Driver', '62541785', 'samir@big4.com', 'scooter', 'TUN-1234', 'ACTIVE', 'Downtown', 500.00, '2026-05-01', 4.75, '2026-05-11 09:00:00', '2026-05-11 09:00:00', 'seed', 'seed', 36.8065, 10.1815, '2026-05-11 09:10:00', 'LIC-001', '2027-05-01', 1, NULL, 4.75, 120);

INSERT INTO `fleet_car` (`car_id`, `make`, `model`, `license_plate`, `vehicle_type`, `delivery_man_id`, `color`, `year`, `fuel_type`, `mileage`, `registration_date`, `last_maintenance_date`, `status`, `latitude`, `longitude`, `last_update`, `battery_level`, `fuel_level`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Toyota', 'Corolla', '123-TUN-456', 'Sedan', 1, 'White', 2022, 'Gasoline', 24000, '2022-02-14', '2026-04-12', 'AVAILABLE', 36.8065, 10.1815, '2026-05-11 09:10:00', NULL, 75, 1, '2026-05-11 09:00:00', '2026-05-11 09:10:00');

UPDATE `delivery_man` SET `current_car_id` = 1 WHERE `delivery_man_id` = 1;

INSERT INTO `assignment_history` (`id`, `car_id`, `delivery_man_id`, `assigned_by_id`, `assigned_at`, `unassigned_at`, `reason`, `status`) VALUES
(1, 1, 1, 1, '2026-05-11 09:15:00', NULL, 'manual', 'active');

INSERT INTO `delivery` (`delivery_id`, `order_id`, `delivery_man_id`, `delivery_address`, `recipient_name`, `recipient_phone`, `pickup_location`, `status`, `scheduled_date`, `actual_delivery_date`, `estimated_time`, `current_latitude`, `current_longitude`, `delivery_notes`, `rating`, `created_at`, `updated_at`, `created_by`, `updated_by`, `cart_items`, `order_total`, `fleet_car_id`, `license_plate`, `restaurant_rating`, `destination_latitude`, `destination_longitude`, `driver_latitude`, `driver_longitude`, `destination_lat`, `destination_lng`, `candidate_delivery_men`, `candidate_index`) VALUES
(1, 1, 1, 'Rue Habib Bourguiba, Tunis', 'Client One', '22222222', 'Restaurant A', 'ASSIGNED', NULL, NULL, 25, 36.8065, 10.1815, 'Handle with care', NULL, '2026-05-11 09:30:00', '2026-05-11 09:30:00', 'seed', 'seed', '[{\"name\":\"Margherita Pizza\",\"price\":24.90}]', 24.90, 1, '123-TUN-456', 5, 36.8070, 10.1820, 36.8065, 10.1815, 36.8070, 10.1820, '[1]', 0);

INSERT INTO `delivery_feature` (`id`, `delivery_id`, `features`, `created_at`) VALUES
(1, 1, '{\"fragile\":true,\"cold_chain\":false}', '2026-05-11 09:31:00');

INSERT INTO `gps_log` (`id`, `car_id`, `delivery_man_id`, `latitude`, `longitude`, `accuracy`, `altitude`, `speed`, `bearing`, `timestamp`, `source`) VALUES
(1, 1, 1, 36.8065, 10.1815, 5, 20, 12.5, 180, '2026-05-11 09:32:00', 'mobile');

INSERT INTO `menu` (`id`, `title`, `description`, `isActive`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Signature Classics', 'Default menu for testing', 1, 1, '2026-05-11 09:00:00', '2026-05-11 09:00:00');

INSERT INTO `dish` (`id`, `menu_id`, `name`, `description`, `base_price`, `available`, `stock_quantity`, `image_url`, `created_at`, `updated_at`) VALUES
(1, 1, 'Margherita Pizza', 'Stone-baked pizza with tomato, mozzarella, and basil.', 24.90, 1, 60, NULL, '2026-05-11 09:00:00', '2026-05-11 09:00:00');

INSERT INTO `ingredient` (`id`, `name`, `quantityInStock`, `unit`, `createdAt`, `minStockLevel`, `unitCost`, `expiryDate`) VALUES
(1, 'Tomato', 50, 'kg', '2026-05-11 08:00:00', 10, 2.50, '2026-06-10');

INSERT INTO `dish_ingredient` (`dish_id`, `ingredient_id`, `quantity_required`) VALUES
(1, 1, 0.35);

INSERT INTO `food_donation_event` (`donation_event_id`, `event_date`, `total_quantity`, `charity_name`, `status`, `delivery_id`, `calendar_event_id`, `sms_reminder_sent`, `created_at`, `updated_at`, `reminder_sent_at`) VALUES
(1, '2026-05-20 10:00:00', 12, 'Hope Center', 'Scheduled', 1, NULL, 0, '2026-05-11 09:40:00', '2026-05-11 09:40:00', NULL);

INSERT INTO `food_donation_items` (`donation_event_id`, `item_id`, `quantity`) VALUES
(1, 1, 5);

INSERT INTO `donation_event_item` (`id`, `event_id`, `item_id`, `quantity`) VALUES
(1, 1, 1, 2);

INSERT INTO `event_registration` (`id`, `donation_event_id`, `user_id`, `created_at`) VALUES
(1, 1, 2, '2026-05-11 09:45:00');

INSERT INTO `event_ratings` (`rating_id`, `donation_event_id`, `user_id`, `event_rating`, `food_rating`, `comment`, `created_at`) VALUES
(1, 1, 2, 5, 4, 'Great event and food.', '2026-05-11 10:10:00');

INSERT INTO `ratings` (`rating_id`, `donation_event_id`, `user_id`, `event_rating`, `food_rating`, `comment`, `created_at`) VALUES
(1, 1, 2, 4, 5, 'Delicious and timely.', '2026-05-11 10:05:00');

INSERT INTO `notifications` (`notification_id`, `user_id`, `donation_event_id`, `message`, `notification_type`, `status`, `scheduled_time`, `sent_at`, `is_read`, `created_at`) VALUES
(1, 1, 1, 'Donation event scheduled.', 'EVENT', 'SCHEDULED', '2026-05-11 10:00:00', NULL, 0, '2026-05-11 10:00:00');

INSERT INTO `notification` (`id`, `recipient_id`, `type`, `title`, `message`, `related_entity`, `related_entity_id`, `is_read`, `created_at`, `read_at`) VALUES
(1, 1, 'ORDER', 'New Order', 'A new order has been placed.', 'orders', 1, 0, '2026-05-11 09:35:00', NULL);

INSERT INTO `restaurant_table` (`table_id`, `capacity`, `status`) VALUES
(1, 4, 'AVAILABLE');

INSERT INTO `reservation` (`reservation_id`, `table_id`, `client_id`, `reservation_date`, `reservation_time`, `number_of_guests`, `status`) VALUES
(1, 1, 1, '2026-05-12', '19:30:00', 2, 'CONFIRMED');

INSERT INTO `orders` (`order_id`, `reservation_id`, `client_id`, `order_type`, `order_date`, `delivery_address`, `status`, `total_amount`, `cart_items`, `payment_method`) VALUES
(1, 1, 1, 'DINE_IN', '2026-05-11 09:25:00', NULL, 'PENDING', 24.90, '[{\"name\":\"Margherita Pizza\",\"price\":24.90}]', 'CASH');

INSERT INTO `sustainability_metrics` (`metric_id`, `donation_event_id`, `total_quantity`, `meals_provided`, `co2_saved_kg`, `cost_saved`, `calculated_at`) VALUES
(1, 1, 12, 30, 15.50, 120.00, '2026-05-11 10:20:00');

INSERT INTO `password_reset_token` (`id`, `user_id`, `token_hash`, `expires_at`, `created_at`, `used_at`) VALUES
(1, 1, 'FAKE_HASH_TOKEN', '2026-05-12 10:00:00', '2026-05-11 10:00:00', NULL);

INSERT INTO `messenger_messages` (`id`, `body`, `headers`, `queue_name`, `created_at`, `available_at`, `delivered_at`) VALUES
(1, 'test message', '{\"type\":\"test\"}', 'default', '2026-05-11 10:00:00', '2026-05-11 10:00:00', NULL);

INSERT INTO `audit_log` (`id`, `actor_id`, `action`, `entity_type`, `entity_id`, `changes`, `timestamp`, `ip_address`, `user_agent`) VALUES
(1, 1, 'CREATE', 'orders', 1, '{\"status\":[null,\"PENDING\"]}', '2026-05-11 09:26:00', '127.0.0.1', 'CLI');

INSERT INTO `wasterecord` (`id`, `ingredientId`, `quantityWasted`, `wasteType`, `date`, `reason`) VALUES
(1, 1, 2.5, 'Preparation Loss', '2026-05-11', 'Trim waste');

INSERT INTO `webauthn_credential` (`id`, `credential_id`, `user_handle`, `source_json`, `created_at`, `updated_at`, `user_id`, `public_key`, `counter`) VALUES
(1, 'FAKE_CRED_ID', '1', '{\"type\":\"public-key\"}', '2026-05-11 09:50:00', '2026-05-11 09:50:00', 1, 'FAKE_PUBLIC_KEY', 0);

INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
('DoctrineMigrations\\\\Version20260511000100', '2026-05-11 10:30:00', 45);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
