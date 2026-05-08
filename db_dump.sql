-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 23, 2026 at 12:52 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `project`
--

-- --------------------------------------------------------

--
-- Table structure for table `assignment_history`
--

CREATE TABLE `assignment_history` (
  `id` int(11) NOT NULL,
  `car_id` bigint(20) NOT NULL,
  `delivery_man_id` bigint(20) NOT NULL,
  `assigned_by_id` bigint(20) DEFAULT NULL,
  `assigned_at` datetime NOT NULL,
  `unassigned_at` datetime DEFAULT NULL,
  `reason` varchar(40) NOT NULL,
  `status` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL,
  `actor_id` bigint(20) DEFAULT NULL,
  `action` varchar(30) NOT NULL,
  `entity_type` varchar(60) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`changes`)),
  `timestamp` datetime NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery`
--

CREATE TABLE `delivery` (
  `delivery_id` bigint(20) NOT NULL,
  `order_id` bigint(20) NOT NULL,
  `delivery_man_id` bigint(20) DEFAULT NULL,
  `delivery_address` varchar(255) NOT NULL,
  `recipient_name` varchar(100) DEFAULT NULL,
  `recipient_phone` varchar(20) DEFAULT NULL,
  `pickup_location` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'PENDING',
  `scheduled_date` timestamp NULL DEFAULT NULL,
  `actual_delivery_date` timestamp NULL DEFAULT current_timestamp(),
  `estimated_time` int(11) DEFAULT NULL,
  `current_latitude` decimal(10,8) DEFAULT NULL,
  `current_longitude` decimal(11,8) DEFAULT NULL,
  `delivery_notes` text DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cart_items` longtext DEFAULT NULL,
  `order_total` decimal(10,2) DEFAULT NULL,
  `fleet_car_id` bigint(20) DEFAULT NULL,
  `license_plate` varchar(255) DEFAULT NULL,
  `restaurant_rating` int(11) DEFAULT NULL,
  `destination_latitude` decimal(10,6) DEFAULT NULL,
  `destination_longitude` decimal(10,6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery`
--

INSERT INTO `delivery` (`delivery_id`, `order_id`, `delivery_man_id`, `delivery_address`, `recipient_name`, `recipient_phone`, `pickup_location`, `status`, `scheduled_date`, `actual_delivery_date`, `estimated_time`, `current_latitude`, `current_longitude`, `delivery_notes`, `rating`, `created_at`, `updated_at`, `cart_items`, `order_total`, `fleet_car_id`, `license_plate`, `restaurant_rating`, `destination_latitude`, `destination_longitude`) VALUES
(10, 1, NULL, 'qatar', 'zazi', '12345678', NULL, 'DELIVERED', NULL, '2026-02-24 22:45:15', NULL, NULL, NULL, NULL, NULL, '2026-02-24 22:45:15', '2026-04-08 19:43:31', NULL, NULL, NULL, NULL, 5, NULL, NULL),
(11, 1111, NULL, 'hammamet', 'emna', '50916717', NULL, 'DELIVERED', NULL, '2026-03-02 19:38:15', NULL, NULL, NULL, NULL, NULL, '2026-03-02 19:38:15', '2026-03-02 19:38:39', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(12, 662750, 3, '751 qatar', 'NOUR Babay', '12345678', 'esprit', 'ASSIGNED', NULL, NULL, 30, NULL, NULL, 'no hrissa', NULL, '2026-04-08 18:49:44', '2026-04-10 08:35:52', 'test (4.00 TND)\nTomato Chicken Stew (22.50 TND)\nCheese Veggie Bake (18.50 TND)\nCheese Veggie Bake (18.50 TND)\nPotato Carrot Saute (13.50 TND)\nButtery Garlic Pasta (17.00 TND)', 94.00, NULL, '12QA55', NULL, NULL, NULL),
(15, 759514, 2, '225 tuscani', 'NOUR Babay', '12345678', 'esprit', 'ASSIGNED', NULL, NULL, 30, NULL, NULL, 'no toppings', NULL, '2026-04-10 07:49:30', '2026-04-10 07:49:30', 'test (4.00 TND)', 4.00, NULL, NULL, NULL, NULL, NULL),
(16, 979657, 2, '225 tuscani', 'NOUR Babay', '12345678', 'esprit', 'ASSIGNED', NULL, NULL, 30, NULL, NULL, '', NULL, '2026-04-10 07:50:20', '2026-04-10 07:50:20', 'Creamy Tomato Pasta (19.00 TND)', 19.00, NULL, NULL, NULL, NULL, NULL),
(17, 195816, 2, '225 tuscani', 'NOUR Babay', '12345678', 'esprit', 'ASSIGNED', NULL, NULL, 30, NULL, NULL, '', NULL, '2026-04-10 08:30:54', '2026-04-10 08:30:54', 'test (4.00 TND)', 4.00, NULL, NULL, NULL, NULL, NULL),
(18, 232452, 2, '751 doha', 'NOUR Babay', '12345678', 'esprit b', 'ASSIGNED', NULL, NULL, 30, NULL, NULL, '', NULL, '2026-04-10 08:50:04', '2026-04-10 08:50:04', 'Creamy Tomato Pasta (19.00 TND)', 19.00, NULL, NULL, NULL, NULL, NULL),
(19, 224716, 2, '751 doha', 'NOUR Babay', '12345678', 'esprit b', 'ASSIGNED', NULL, NULL, 30, NULL, NULL, '', NULL, '2026-04-10 08:59:18', '2026-04-10 08:59:18', 'Creamy Tomato Pasta (19.00 TND)', 19.00, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `delivery_man`
--

CREATE TABLE `delivery_man` (
  `delivery_man_id` bigint(20) NOT NULL,
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
  `latitude` decimal(10,6) DEFAULT NULL,
  `longitude` decimal(10,6) DEFAULT NULL,
  `last_location_update` datetime DEFAULT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `license_expiry_date` date DEFAULT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `current_car_id` bigint(20) DEFAULT NULL,
  `average_rating` double DEFAULT NULL,
  `total_deliveries` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `delivery_man`
--

INSERT INTO `delivery_man` (`delivery_man_id`, `name`, `phone`, `email`, `vehicle_type`, `vehicle_number`, `status`, `address`, `salary`, `date_of_joining`, `rating`, `created_at`, `updated_at`, `latitude`, `longitude`, `last_location_update`, `license_number`, `license_expiry_date`, `is_available`, `current_car_id`, `average_rating`, `total_deliveries`) VALUES
(1, 'babay', '12345622', 'babay@gmail.com', NULL, '123tun456', NULL, 'aqtrq', 200.00, '2026-03-03', 0.00, '2026-03-02 23:59:41', '2026-04-08 18:55:22', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0),
(2, 'yasmin', '96930620', 'yasmin@gmail.com', 'chery', '123456', 'ACTIVE', '', 0.00, '2026-03-03', 0.00, '2026-03-03 00:22:08', '2026-03-03 00:22:08', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0),
(3, 'gggg', '99999999', 'hhhh@gmail.com', 'car', '123tun147', 'ACTIVE', '', 0.00, '2026-03-03', 0.00, '2026-03-03 06:56:53', '2026-03-03 06:56:53', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `dish`
--

CREATE TABLE `dish` (
  `id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `available` tinyint(1) NOT NULL DEFAULT 1,
  `stock_quantity` int(11) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dish`
--

INSERT INTO `dish` (`id`, `menu_id`, `name`, `description`, `base_price`, `available`, `stock_quantity`, `image_url`, `created_at`, `updated_at`) VALUES
(1, 1, 'Donated Food Item 1', 'Food item for donation', 0.00, 1, 1000, NULL, '2026-02-13 16:41:12', '2026-02-13 16:41:12'),
(2, 1, 'Donated Food Item 2', 'Food item for donation', 0.00, 1, 1000, NULL, '2026-02-13 16:41:12', '2026-02-13 16:41:12'),
(3, 1, 'Donated Food Item 3', 'Food item for donation', 0.00, 1, 1000, NULL, '2026-02-13 16:41:12', '2026-02-13 16:41:12'),
(5, 9, 'test', 'hey', 4.00, 1, 9, '', '2026-02-24 23:39:20', '2026-02-24 23:39:20'),
(3001, 1001, 'Tomato Chicken Stew', 'High near-expiry usage dish', 22.50, 1, 50, '', '2026-02-23 20:03:13', '2026-02-23 20:03:13'),
(3002, 1001, 'Creamy Tomato Pasta', 'Tomato + milk based pasta', 19.00, 1, 70, '', '2026-02-23 20:03:13', '2026-02-23 20:03:13'),
(3003, 1001, 'Onion Garlic Rice Bowl', 'Onion and garlic focused', 16.00, 1, 80, '', '2026-02-23 20:03:13', '2026-02-23 20:03:13'),
(3004, 1001, 'Chicken Rice Plate', 'Chicken with rice and oil', 24.00, 1, 40, '', '2026-02-23 20:03:13', '2026-02-23 20:03:13'),
(3005, 1002, 'Cheese Veggie Bake', 'Vegetable + cheese tray', 18.50, 1, 45, '', '2026-02-23 20:03:13', '2026-02-23 20:03:13'),
(3006, 1002, 'Potato Carrot Saute', 'Simple hot side dish', 13.50, 1, 90, '', '2026-02-23 20:03:13', '2026-02-23 20:03:13'),
(3007, 1002, 'Buttery Garlic Pasta', 'Butter garlic pasta bowl', 17.00, 1, 60, '', '2026-02-23 20:03:13', '2026-02-23 20:03:13'),
(3008, 1002, 'Archived Testing Dish', 'Unavailable test record', 11.00, 0, 25, '', '2026-02-23 20:03:13', '2026-02-23 20:03:13');

-- --------------------------------------------------------

--
-- Table structure for table `dish_ingredient`
--

CREATE TABLE `dish_ingredient` (
  `dish_id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `quantity_required` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dish_ingredient`
--

INSERT INTO `dish_ingredient` (`dish_id`, `ingredient_id`, `quantity_required`) VALUES
(3001, 2001, 2.6),
(3001, 2002, 1.8),
(3001, 2004, 0.7),
(3001, 2005, 0.25),
(3001, 2007, 0.2),
(3002, 2001, 1.9),
(3002, 2003, 1.1),
(3002, 2008, 0.45),
(3002, 2011, 1.4),
(3002, 2014, 0.2),
(3003, 2004, 1.6),
(3003, 2005, 0.55),
(3003, 2006, 1.3),
(3003, 2012, 0.1),
(3004, 2002, 1.7),
(3004, 2006, 1.1),
(3004, 2007, 0.25),
(3004, 2013, 0.4),
(3005, 2004, 0.5),
(3005, 2008, 0.8),
(3005, 2009, 0.9),
(3005, 2010, 1.2),
(3006, 2004, 0.4),
(3006, 2007, 0.15),
(3006, 2010, 1.8),
(3006, 2013, 1.1),
(3007, 2005, 0.3),
(3007, 2008, 0.25),
(3007, 2011, 1.5),
(3007, 2014, 0.35),
(3008, 2001, 1),
(3008, 2003, 0.7),
(3008, 2015, 0.6);

-- --------------------------------------------------------

--
-- Table structure for table `doctrine_migration_versions`
--

CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `doctrine_migration_versions`
--

INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
('DoctrineMigrations\\Version20250930134306', '2026-04-08 21:28:54', 54),
('DoctrineMigrations\\Version20251014123441', '2026-04-08 21:28:54', 112),
('DoctrineMigrations\\Version20260407120000', '2026-04-08 21:28:54', 176),
('DoctrineMigrations\\Version20260407181736', '2026-04-08 21:28:54', 160),
('DoctrineMigrations\\Version20260407185202', '2026-04-08 21:28:55', 11),
('DoctrineMigrations\\Version20260408121000', '2026-04-08 21:28:55', 31),
('DoctrineMigrations\\Version20260408131617', '2026-04-08 21:29:42', 2),
('DoctrineMigrations\\Version20260408192836', NULL, NULL),
('DoctrineMigrations\\Version20260408200000', NULL, NULL),
('DoctrineMigrations\\Version20260410120000', NULL, NULL),
('DoctrineMigrations\\Version20260416001436', '2026-04-16 02:16:04', 172),
('DoctrineMigrations\\Version20260416110225', NULL, NULL),
('DoctrineMigrations\\Version20260416123000', NULL, NULL),
('DoctrineMigrations\\Version20260416194000', NULL, NULL),
('DoctrineMigrations\\Version20260418123000', '2026-04-18 15:54:19', 154),
('DoctrineMigrations\\Version20260418160500', '2026-04-18 15:54:20', 60),
('DoctrineMigrations\\Version20260418171500', '2026-04-18 16:40:50', 160),
('DoctrineMigrations\\Version20260421120000', '2026-04-21 15:21:25', 115),
('DoctrineMigrations\\Version20260421143000', '2026-04-21 15:43:20', 33),
('DoctrineMigrations\\Version20260421150000', '2026-04-21 17:36:06', 2),
('DoctrineMigrations\\Version20260421193000', '2026-04-21 22:52:54', 320),
('DoctrineMigrations\\Version20260423110000', '2026-04-23 02:04:10', 290),
('DoctrineMigrations\\Version20260423143000', '2026-04-23 09:41:41', 1202);

-- --------------------------------------------------------

--
-- Table structure for table `donation_event_item`
--

CREATE TABLE `donation_event_item` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `donation_event_item`
--

INSERT INTO `donation_event_item` (`id`, `event_id`, `item_id`, `quantity`) VALUES
(5, 78, 3, 1),
(6, 79, 5, 1),
(7, 79, 3001, 1);

-- --------------------------------------------------------

--
-- Table structure for table `event_registration`
--

CREATE TABLE `event_registration` (
  `id` int(11) NOT NULL,
  `donation_event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `event_registration`
--

INSERT INTO `event_registration` (`id`, `donation_event_id`, `user_id`, `created_at`) VALUES
(26, 85, 1, '2026-04-23 12:26:31');

-- --------------------------------------------------------

--
-- Table structure for table `fleet_car`
--

CREATE TABLE `fleet_car` (
  `car_id` bigint(20) NOT NULL,
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
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `food_donation_event`
--

CREATE TABLE `food_donation_event` (
  `donation_event_id` int(11) NOT NULL,
  `event_date` datetime NOT NULL,
  `total_quantity` int(11) NOT NULL,
  `charity_name` varchar(100) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Scheduled',
  `delivery_id` bigint(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `calendar_event_id` varchar(255) DEFAULT NULL,
  `sms_reminder_sent` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `food_donation_event`
--

INSERT INTO `food_donation_event` (`donation_event_id`, `event_date`, `total_quantity`, `charity_name`, `status`, `delivery_id`, `created_at`, `updated_at`, `calendar_event_id`, `sms_reminder_sent`) VALUES
(78, '2026-04-18 17:43:00', 2, 'uuuu', 'Completed', NULL, '2026-04-18 14:43:57', '2026-04-21 20:32:57', NULL, 1),
(79, '2026-05-09 19:50:00', 3, 'pearl', 'Scheduled', NULL, '2026-04-18 15:51:08', '2026-04-18 18:55:06', NULL, 0),
(84, '2026-04-23 11:30:00', 5, 'test', 'In Progress', NULL, '2026-04-22 17:30:03', '2026-04-22 23:49:05', NULL, 0),
(85, '2026-04-24 09:50:00', 3, 'noora', 'Scheduled', NULL, '2026-04-22 18:19:20', '2026-04-22 18:19:20', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `food_donation_items`
--

CREATE TABLE `food_donation_items` (
  `donation_event_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `food_donation_items`
--

INSERT INTO `food_donation_items` (`donation_event_id`, `item_id`, `quantity`) VALUES
(78, 3, 1),
(79, 5, 1),
(79, 3001, 1),
(84, 3001, 1),
(84, 3002, 1),
(84, 3003, 1),
(85, 2, 1),
(85, 3001, 1);

-- --------------------------------------------------------

--
-- Table structure for table `gps_log`
--

CREATE TABLE `gps_log` (
  `id` int(11) NOT NULL,
  `car_id` bigint(20) NOT NULL,
  `delivery_man_id` bigint(20) DEFAULT NULL,
  `latitude` decimal(10,6) NOT NULL,
  `longitude` decimal(10,6) NOT NULL,
  `accuracy` int(11) DEFAULT NULL,
  `altitude` double DEFAULT NULL,
  `speed` double DEFAULT NULL,
  `bearing` double DEFAULT NULL,
  `timestamp` datetime NOT NULL,
  `source` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ingredient`
--

CREATE TABLE `ingredient` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `quantityInStock` double NOT NULL,
  `unit` varchar(50) NOT NULL,
  `createdAt` datetime DEFAULT NULL,
  `minStockLevel` double NOT NULL,
  `unitCost` decimal(10,2) NOT NULL,
  `expiryDate` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ingredient`
--

INSERT INTO `ingredient` (`id`, `name`, `quantityInStock`, `unit`, `createdAt`, `minStockLevel`, `unitCost`, `expiryDate`) VALUES
(2001, 'Tomato', 0, 'kg', '2026-02-23 21:03:13', 20, 2.40, '2026-02-25'),
(2002, 'Chicken Breast', 0, 'kg', '2026-02-23 21:03:13', 14, 9.80, '2026-02-25'),
(2003, 'Milk', 0, 'l', '2026-02-23 21:03:13', 10, 2.10, '2026-02-26'),
(2004, 'Onion', 0, 'kg', '2026-02-23 21:03:13', 20, 1.70, '2026-02-24'),
(2005, 'Garlic', 0, 'kg', '2026-02-23 21:03:13', 8, 3.00, '2026-02-24'),
(2006, 'Rice', 130, 'kg', '2026-02-23 21:03:13', 45, 1.30, '2026-04-24'),
(2007, 'Olive Oil', 25.2, 'l', '2026-02-23 21:03:13', 10, 6.50, '2026-06-23'),
(2008, 'Cheese', 0, 'kg', '2026-02-23 21:03:13', 9, 10.50, '2026-03-03'),
(2009, 'Bell Pepper', 0, 'kg', '2026-02-23 21:03:13', 7, 2.60, '2026-02-28'),
(2010, 'Potato', 0, 'kg', '2026-02-23 21:03:13', 35, 1.10, '2026-03-20'),
(2011, 'Pasta', 74.4, 'kg', '2026-02-23 21:03:13', 25, 1.90, '2026-05-24'),
(2012, 'Parsley', 0, 'kg', '2026-02-23 21:03:13', 3, 4.20, '2026-02-27'),
(2013, 'Carrot', 0, 'kg', '2026-02-23 21:03:13', 10, 1.90, '2026-03-01'),
(2014, 'Butter', 0, 'kg', '2026-02-23 21:03:13', 6, 5.80, '2026-03-02'),
(2015, 'Flour', 75, 'kg', '2026-02-23 21:03:13', 25, 1.20, '2026-06-23'),
(2018, 'eggs', 0, 'KG', '2026-02-25 01:29:37', 5, 1.00, '2026-02-27');

-- --------------------------------------------------------

--
-- Table structure for table `menu`
--

CREATE TABLE `menu` (
  `id` int(11) NOT NULL,
  `title` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `isActive` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu`
--

INSERT INTO `menu` (`id`, `title`, `description`, `isActive`, `created_at`, `updated_at`) VALUES
(1, 'Main Menu', 'All available dishes', 1, '2026-02-10 08:38:48', '2026-02-10 08:38:48'),
(2, 'Main Menu', 'All available dishes', 1, '2026-02-10 08:48:36', '2026-02-10 08:48:36'),
(3, 'Main Menu', 'All available dishes', 1, '2026-02-12 13:44:17', '2026-02-12 13:44:17'),
(4, 'italian menu v2', 'Updated description only', 1, '2026-02-12 13:46:08', '2026-02-12 15:54:45'),
(5, 'Italian Menu', 'Pasta, Pizza, Risotto', 1, '2026-02-12 14:03:32', '2026-02-12 14:03:32'),
(6, 'Italian Menu', 'Pasta, Pizza, Risotto', 1, '2026-02-12 14:16:39', '2026-02-12 14:16:39'),
(7, 'Italian Menu', 'Pasta, Pizza, Risotto', 1, '2026-02-12 14:17:35', '2026-02-12 14:17:35'),
(8, 'Italian Menu', 'Pasta, Pizza, Risotto', 1, '2026-02-12 14:19:23', '2026-02-12 14:19:23'),
(9, 'Italian Menu', 'Pasta, Pizza, Risotto', 1, '2026-02-12 14:21:02', '2026-02-12 14:21:02'),
(10, 'italian menu v2', NULL, 0, '2026-02-12 14:23:24', '2026-02-12 14:23:46'),
(11, 'Italian Menu', 'Pasta, Pizza, Risotto', 1, '2026-02-12 14:23:46', '2026-02-12 14:23:46'),
(12, 'Italian Menu', 'Pasta, Pizza, Risotto', 1, '2026-02-12 15:54:45', '2026-02-12 15:54:45'),
(1001, 'Donation Test Menu', 'Menu for donation optimization testing', 1, '2026-02-23 20:03:13', '2026-02-23 20:03:13'),
(1002, 'Kitchen Test Menu', 'Extra menu for recipe variety', 1, '2026-02-23 20:03:13', '2026-02-23 20:03:13');

-- --------------------------------------------------------

--
-- Table structure for table `messenger_messages`
--

CREATE TABLE `messenger_messages` (
  `id` bigint(20) NOT NULL,
  `body` longtext NOT NULL,
  `headers` longtext NOT NULL,
  `queue_name` varchar(190) NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `available_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `delivered_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `id` int(11) NOT NULL,
  `recipient_id` bigint(20) NOT NULL,
  `type` varchar(30) NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` longtext NOT NULL,
  `related_entity` varchar(60) DEFAULT NULL,
  `related_entity_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `reservation_id` int(11) DEFAULT NULL,
  `client_id` int(11) NOT NULL,
  `order_type` varchar(20) NOT NULL,
  `order_date` datetime NOT NULL,
  `delivery_address` varchar(200) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `cart_items` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_token`
--

CREATE TABLE `password_reset_token` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `used_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `rating_id` int(11) NOT NULL,
  `donation_event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_rating` int(11) DEFAULT NULL,
  `food_rating` int(11) DEFAULT NULL,
  `comment` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservation`
--

CREATE TABLE `reservation` (
  `reservation_id` int(11) NOT NULL,
  `table_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `reservation_date` date NOT NULL,
  `reservation_time` time NOT NULL,
  `number_of_guests` int(11) NOT NULL,
  `status` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `restaurant_table`
--

CREATE TABLE `restaurant_table` (
  `table_id` int(11) NOT NULL,
  `capacity` int(11) NOT NULL,
  `status` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sustainability_metrics`
--

CREATE TABLE `sustainability_metrics` (
  `metric_id` int(11) NOT NULL,
  `donation_event_id` int(11) NOT NULL,
  `total_quantity` int(11) NOT NULL,
  `meals_provided` int(11) NOT NULL,
  `co2_saved_kg` decimal(10,2) NOT NULL,
  `cost_saved` decimal(12,2) DEFAULT NULL,
  `calculated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` bigint(20) NOT NULL,
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
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `email`, `password_hash`, `role`, `reference_id`, `full_name`, `phone`, `address`, `first_name`, `last_name`, `banned`, `phone_number`, `created_at`, `updated_at`, `is_active`, `is_verified`, `profile_image`) VALUES
(1, 'admin@big4.com', '$2y$12$MY6GWEdvPvy/VCJ5yGgvpuxAiDbGG6zATNBFrX94C/bgxf1l2dqP.', 'ROLE_ADMIN', NULL, 'System Admin', '+216 94289221', '', 'System', 'Admin', 0, NULL, '2026-04-23 01:04:10', '2026-04-23 01:04:10', 1, 0, NULL),
(3, 'babay', 'qP19wOG/cYdPe64BPYmwfaMPRL2wAcbkQHE4jOb6OA8=', 'CLIENT', NULL, 'mira', '+216 94289221', NULL, 'mira', NULL, 0, NULL, '2026-04-23 01:04:10', '2026-04-23 01:04:10', 1, 0, NULL),
(4, 'noor@gmail.com', 'SDAp1SYhn4Fujo9qneB7QiYz26GA/8JvqsIoYqAXUZ8=', 'CLIENT', NULL, 'noor', '+216 94289221', NULL, 'noor', NULL, 0, NULL, '2026-04-23 01:04:10', '2026-04-23 01:04:10', 1, 0, NULL),
(5, 'emna+duplicate-5@gmail.com', '9k7oKp9KBFFSgFn2MC0+xnxaPTYWOSqGv4jfS7RRo7M=', 'CLIENT', NULL, 'emna', '23051021', NULL, 'emna', NULL, 0, NULL, '2026-04-23 01:04:10', '2026-04-23 01:04:10', 1, 0, NULL),
(8, 'nada@gmail.com', 'A6xnQhbz4Vx2HuGl4lXwZ5U2I8iziLRFnhP5eNfIRvQ=', 'CLIENT', NULL, 'emna dridi', '23051021', NULL, 'emna', 'dridi', 0, NULL, '2026-04-23 01:04:10', '2026-04-23 01:04:10', 1, 0, NULL),
(9, 'delivery@gmail.com', 'A6xnQhbz4Vx2HuGl4lXwZ5U2I8iziLRFnhP5eNfIRvQ=', 'CLIENT', NULL, 'delivery', '92702804', NULL, 'delivery', NULL, 0, NULL, '2026-04-23 01:04:10', '2026-04-23 01:04:10', 1, 0, NULL),
(10, 'zayneb@gmail.com', 'A6xnQhbz4Vx2HuGl4lXwZ5U2I8iziLRFnhP5eNfIRvQ=', 'CLIENT', NULL, 'zayneb', '96930620', NULL, 'zayneb', NULL, 0, NULL, '2026-04-23 01:04:10', '2026-04-23 01:04:10', 1, 0, NULL),
(12, 'eya@gmail.com', 'A6xnQhbz4Vx2HuGl4lXwZ5U2I8iziLRFnhP5eNfIRvQ=', 'CLIENT', NULL, 'eya', '999999', NULL, 'eya', NULL, 0, NULL, '2026-04-23 01:04:10', '2026-04-23 01:04:10', 1, 0, NULL),
(13, 'noor@email.com', '$2y$13$7VbbtMcmH13PZ8vX0PgiqOl5X9areJUM1ccbW8v4IlYfExwtm5VOS', 'ROLE_CLIENT', NULL, NULL, '12345678', NULL, 'NOUR', 'Babay', 0, NULL, '2026-04-23 01:04:10', '2026-04-23 01:04:10', 1, 0, NULL),
(14, 'admin+legacy-14@big4.com', '6G94qKPK8LYNjnTllCqm2G3BUM08AzOK7yW30tfjrMc=', 'ADMIN', NULL, 'System Admin', NULL, NULL, NULL, NULL, 0, NULL, '2026-04-23 01:04:10', '2026-04-23 01:04:10', 1, 0, NULL),
(15, 'ali@email.com', '$2y$13$VIYYgeVCcQ/RyUsKUpM2TudzvN4rLGfMe2mrkeiZS3cNEZCOrNzkm', 'ROLE_DELIVERY_MAN', NULL, NULL, '12345678', NULL, 'ali DELIVERY', 'boujemaa', 0, NULL, '2026-04-23 01:04:10', '2026-04-23 01:04:10', 1, 0, NULL),
(16, 'mira@gmail.com', '$2y$13$32c4J2g.HXVq1.fh4Nt.OeMdV.NGDUp9XUXbwQV2mXa7iLlPR/X1y', 'ROLE_CLIENT', NULL, NULL, '94289221', NULL, 'mira', 'jaber', 0, NULL, '2026-04-23 01:04:10', '2026-04-23 01:04:10', 1, 0, NULL),
(17, 'haya@gmail.com', '$2y$13$/0s9hdh.3DOUWFCoGPkAROsuP8AK3fvtaKAyv1TRb.m6i6rwf.Fh.', 'ROLE_CLIENT', NULL, NULL, '+21694289221', NULL, 'haya', 'althany', 0, NULL, '2026-04-23 01:04:10', '2026-04-23 01:04:10', 1, 0, NULL),
(18, 'emna@gmail.com', '$2y$13$8OELtAlXC0i9alfgVnJpgOybBCcS5VH902UX8ixrkxhzbpK/1newi', 'ROLE_CLIENT', NULL, NULL, '96930620', NULL, 'emna', 'dridi', 0, NULL, '2026-04-23 01:04:10', '2026-04-23 01:04:10', 1, 0, NULL),
(19, 'yasmine@gmail.com', '$2y$13$JgkhgSktyROPaMncr4vPDOoQRUZ72cqVn3DMXeQQZHt2lVvK3wvyO', 'ROLE_CLIENT', NULL, NULL, '96930620', NULL, 'yasmine', 'rjab', 0, NULL, '2026-04-23 01:04:10', '2026-04-23 01:04:10', 1, 0, NULL),
(20, 'emna1@gmail.com', '$2y$13$v39nEe0GAQOmJc0owg1T2.oE6/VZyVGq0Y1mphceygiu2ldBKrD5K', 'ROLE_CLIENT', NULL, NULL, '96930620', NULL, 'emna', 'eyaa', 0, NULL, '2026-04-23 01:04:10', '2026-04-23 01:04:10', 1, 0, NULL),
(21, 'hassen@gmail.com', '$2y$12$3lp64.NsRd6nnIXVthTVLuZiRpeiwDhM1tzsYhipWQl0WUOwqiG3.', 'ROLE_CLIENT', NULL, NULL, '92702804', NULL, 'hassen', 'amor', 0, NULL, '2026-04-23 01:04:10', '2026-04-23 01:04:10', 1, 0, NULL),
(22, 'iheb@gmail.com', '$2y$12$H5uy4dA8Ux0u7jV9NsNKyOUdIECxE5ByWQAGHXCp6z9mi/xUXiQqC', 'ROLE_CLIENT', NULL, NULL, '96930620', NULL, 'iheb', 'dridi', 0, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 1, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user1`
--

CREATE TABLE `user1` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL,
  `status` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wasterecord`
--

CREATE TABLE `wasterecord` (
  `id` int(11) NOT NULL,
  `ingredientId` int(11) NOT NULL,
  `quantityWasted` double NOT NULL,
  `wasteType` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `reason` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wasterecord`
--

INSERT INTO `wasterecord` (`id`, `ingredientId`, `quantityWasted`, `wasteType`, `date`, `reason`) VALUES
(1, 2001, 3, 'Preparation Loss', '2026-02-24', 'Loss'),
(2, 2013, 5, 'Spoilage', '2026-02-24', 'spoilage'),
(3, 2003, 4, 'Expired', '2026-02-24', 'expired'),
(4, 2012, 6, 'Spoilage', '2026-02-24', 'waste'),
(5, 2003, 11, 'Preparation Loss', '2026-02-24', 'waste'),
(7, 2018, 6, 'Expired', '2026-02-25', 'expired'),
(8, 2005, 18.5, 'Expired', '2026-03-01', 'Auto-recorded: ingredient expired and removed from stock'),
(9, 2004, 38.2, 'Expired', '2026-03-01', 'Auto-recorded: ingredient expired and removed from stock'),
(10, 2001, 8, 'Expired', '2026-03-01', 'Auto-recorded: ingredient expired and removed from stock'),
(11, 2002, 10.8, 'Expired', '2026-03-01', 'Auto-recorded: ingredient expired and removed from stock'),
(12, 2003, 4.6, 'Expired', '2026-03-01', 'Auto-recorded: ingredient expired and removed from stock'),
(13, 2012, 2, 'Expired', '2026-03-01', 'Auto-recorded: ingredient expired and removed from stock'),
(14, 2018, 2, 'Expired', '2026-03-01', 'Auto-recorded: ingredient expired and removed from stock'),
(15, 2009, 16, 'Expired', '2026-03-01', 'Auto-recorded: ingredient expired and removed from stock'),
(16, 2013, 13, 'Expired', '2026-03-02', 'Auto-recorded: ingredient expired and removed from stock'),
(17, 2014, 13.2, 'Expired', '2026-03-03', 'Auto-recorded: ingredient expired and removed from stock'),
(18, 2008, 18.2, 'Expired', '2026-04-03', 'Auto-recorded: ingredient expired and removed from stock'),
(19, 2010, 95, 'Expired', '2026-04-03', 'Auto-recorded: ingredient expired and removed from stock');

-- --------------------------------------------------------

--
-- Table structure for table `webauthn_credential`
--

CREATE TABLE `webauthn_credential` (
  `id` int(11) NOT NULL,
  `credential_id` varchar(512) NOT NULL,
  `user_handle` varchar(128) NOT NULL,
  `source_json` longtext NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `user_id` bigint(20) DEFAULT NULL,
  `public_key` longtext DEFAULT NULL,
  `counter` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `webauthn_credential`
--

INSERT INTO `webauthn_credential` (`id`, `credential_id`, `user_handle`, `source_json`, `created_at`, `updated_at`, `user_id`, `public_key`, `counter`) VALUES
(1, 'vuTm8fxX1oRdbci8IzT2eabf31o=', '21', '{\"publicKeyCredentialId\":\"vuTm8fxX1oRdbci8IzT2eabf31o\",\"type\":\"public-key\",\"transports\":[\"hybrid\",\"internal\"],\"attestationType\":\"none\",\"trustPath\":[],\"aaguid\":\"00000000-0000-0000-0000-000000000000\",\"credentialPublicKey\":\"pQECAyYgASFYIMyMyrkZVd0dfniupGWfnmf1J41klaWOkXLbLqfkApoVIlggb2cXd5B5VdkrTw845_3cZZSEhMxLEuLp6MOGxwNVkuk\",\"userHandle\":\"MjE\",\"counter\":0,\"backupEligible\":true,\"backupStatus\":true,\"uvInitialized\":true}', '2026-04-23 02:05:41', '2026-04-23 02:05:41', 21, 'pQECAyYgASFYIMyMyrkZVd0dfniupGWfnmf1J41klaWOkXLbLqfkApoVIlggb2cXd5B5VdkrTw845_3cZZSEhMxLEuLp6MOGxwNVkuk', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assignment_history`
--
ALTER TABLE `assignment_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_ASSIGNMENT_HISTORY_CAR_ID` (`car_id`),
  ADD KEY `IDX_ASSIGNMENT_HISTORY_DELIVERY_MAN_ID` (`delivery_man_id`),
  ADD KEY `IDX_ASSIGNMENT_HISTORY_ASSIGNED_BY_ID` (`assigned_by_id`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_AUDIT_LOG_ACTOR_ID` (`actor_id`);

--
-- Indexes for table `delivery`
--
ALTER TABLE `delivery`
  ADD PRIMARY KEY (`delivery_id`),
  ADD UNIQUE KEY `UNIQ_3781EC10F5AA79D0` (`license_plate`),
  ADD KEY `idx_delivery_man_id` (`delivery_man_id`),
  ADD KEY `IDX_3781EC1048CD51AF` (`fleet_car_id`);

--
-- Indexes for table `delivery_man`
--
ALTER TABLE `delivery_man`
  ADD PRIMARY KEY (`delivery_man_id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `vehicle_number` (`vehicle_number`),
  ADD UNIQUE KEY `UNIQ_DELIVERY_MAN_LICENSE_NUMBER` (`license_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_phone` (`phone`),
  ADD KEY `idx_vehicle_type` (`vehicle_type`),
  ADD KEY `IDX_DELIVERY_MAN_CURRENT_CAR_ID` (`current_car_id`);

--
-- Indexes for table `dish`
--
ALTER TABLE `dish`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_dish_menu` (`menu_id`);

--
-- Indexes for table `dish_ingredient`
--
ALTER TABLE `dish_ingredient`
  ADD PRIMARY KEY (`dish_id`,`ingredient_id`),
  ADD KEY `idx_dish_ingredient_ingredient` (`ingredient_id`);

--
-- Indexes for table `doctrine_migration_versions`
--
ALTER TABLE `doctrine_migration_versions`
  ADD PRIMARY KEY (`version`);

--
-- Indexes for table `donation_event_item`
--
ALTER TABLE `donation_event_item`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_event_item_pair` (`event_id`,`item_id`),
  ADD KEY `IDX_778D4F2671F7E88B` (`event_id`),
  ADD KEY `IDX_778D4F26126F525E` (`item_id`);

--
-- Indexes for table `event_registration`
--
ALTER TABLE `event_registration`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_event_user_registration` (`donation_event_id`,`user_id`),
  ADD KEY `IDX_A6A2D3B8837167D6` (`donation_event_id`),
  ADD KEY `IDX_A6A2D3B8A76ED395` (`user_id`);

--
-- Indexes for table `fleet_car`
--
ALTER TABLE `fleet_car`
  ADD PRIMARY KEY (`car_id`),
  ADD UNIQUE KEY `uk_fleet_delivery_man` (`delivery_man_id`);

--
-- Indexes for table `food_donation_event`
--
ALTER TABLE `food_donation_event`
  ADD PRIMARY KEY (`donation_event_id`),
  ADD KEY `idx_event_date` (`event_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_delivery_id` (`delivery_id`);

--
-- Indexes for table `food_donation_items`
--
ALTER TABLE `food_donation_items`
  ADD PRIMARY KEY (`donation_event_id`,`item_id`),
  ADD KEY `idx_item_id` (`item_id`);

--
-- Indexes for table `gps_log`
--
ALTER TABLE `gps_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_GPS_LOG_CAR_ID` (`car_id`),
  ADD KEY `IDX_GPS_LOG_DELIVERY_MAN_ID` (`delivery_man_id`),
  ADD KEY `idx_gps_log_car_timestamp` (`car_id`,`timestamp`);

--
-- Indexes for table `ingredient`
--
ALTER TABLE `ingredient`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ingredient_expiry_stock` (`expiryDate`,`quantityInStock`);

--
-- Indexes for table `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messenger_messages`
--
ALTER TABLE `messenger_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_75EA56E0FB7336F0` (`queue_name`),
  ADD KEY `IDX_75EA56E0E3BD61CE` (`available_at`),
  ADD KEY `IDX_75EA56E016BA31DB` (`delivered_at`);

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_NOTIFICATION_RECIPIENT_ID` (`recipient_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `IDX_E52FFDEEB83297E7` (`reservation_id`);

--
-- Indexes for table `password_reset_token`
--
ALTER TABLE `password_reset_token`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_password_reset_token_hash` (`token_hash`),
  ADD KEY `idx_password_reset_expires_at` (`expires_at`),
  ADD KEY `IDX_5A6E2B3EA76ED395` (`user_id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`rating_id`),
  ADD KEY `IDX_CEB607C9BABCF7FB` (`donation_event_id`),
  ADD KEY `IDX_CEB607C9A76ED395` (`user_id`);

--
-- Indexes for table `reservation`
--
ALTER TABLE `reservation`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `IDX_42C84955ECFF285C` (`table_id`);

--
-- Indexes for table `restaurant_table`
--
ALTER TABLE `restaurant_table`
  ADD PRIMARY KEY (`table_id`);

--
-- Indexes for table `sustainability_metrics`
--
ALTER TABLE `sustainability_metrics`
  ADD PRIMARY KEY (`metric_id`),
  ADD KEY `idx_donation_event_id` (`donation_event_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_email_role` (`email`,`role`);

--
-- Indexes for table `user1`
--
ALTER TABLE `user1`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wasterecord`
--
ALTER TABLE `wasterecord`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ingredientId` (`ingredientId`),
  ADD KEY `idx_wasterecord_ingredient_date` (`ingredientId`,`date`);

--
-- Indexes for table `webauthn_credential`
--
ALTER TABLE `webauthn_credential`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_webauthn_credential_id` (`credential_id`),
  ADD KEY `idx_webauthn_user_handle` (`user_handle`),
  ADD KEY `idx_webauthn_user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assignment_history`
--
ALTER TABLE `assignment_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery`
--
ALTER TABLE `delivery`
  MODIFY `delivery_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `delivery_man`
--
ALTER TABLE `delivery_man`
  MODIFY `delivery_man_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `dish`
--
ALTER TABLE `dish`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3009;

--
-- AUTO_INCREMENT for table `donation_event_item`
--
ALTER TABLE `donation_event_item`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `event_registration`
--
ALTER TABLE `event_registration`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `food_donation_event`
--
ALTER TABLE `food_donation_event`
  MODIFY `donation_event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `gps_log`
--
ALTER TABLE `gps_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ingredient`
--
ALTER TABLE `ingredient`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2019;

--
-- AUTO_INCREMENT for table `menu`
--
ALTER TABLE `menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1003;

--
-- AUTO_INCREMENT for table `messenger_messages`
--
ALTER TABLE `messenger_messages`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_reset_token`
--
ALTER TABLE `password_reset_token`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `rating_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `reservation`
--
ALTER TABLE `reservation`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `restaurant_table`
--
ALTER TABLE `restaurant_table`
  MODIFY `table_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sustainability_metrics`
--
ALTER TABLE `sustainability_metrics`
  MODIFY `metric_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `wasterecord`
--
ALTER TABLE `wasterecord`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `webauthn_credential`
--
ALTER TABLE `webauthn_credential`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignment_history`
--
ALTER TABLE `assignment_history`
  ADD CONSTRAINT `FK_ASSIGNMENT_HISTORY_ASSIGNED_BY` FOREIGN KEY (`assigned_by_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `FK_ASSIGNMENT_HISTORY_CAR` FOREIGN KEY (`car_id`) REFERENCES `fleet_car` (`car_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_ASSIGNMENT_HISTORY_DELIVERY_MAN` FOREIGN KEY (`delivery_man_id`) REFERENCES `delivery_man` (`delivery_man_id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `FK_AUDIT_LOG_ACTOR` FOREIGN KEY (`actor_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `delivery_man`
--
ALTER TABLE `delivery_man`
  ADD CONSTRAINT `FK_DELIVERY_MAN_CURRENT_CAR` FOREIGN KEY (`current_car_id`) REFERENCES `fleet_car` (`car_id`) ON DELETE SET NULL;

--
-- Constraints for table `dish`
--
ALTER TABLE `dish`
  ADD CONSTRAINT `fk_dish_menu` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `donation_event_item`
--
ALTER TABLE `donation_event_item`
  ADD CONSTRAINT `FK_778D4F26126F525E` FOREIGN KEY (`item_id`) REFERENCES `dish` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_778D4F2671F7E88B` FOREIGN KEY (`event_id`) REFERENCES `food_donation_event` (`donation_event_id`) ON DELETE CASCADE;

--
-- Constraints for table `event_registration`
--
ALTER TABLE `event_registration`
  ADD CONSTRAINT `FK_A6A2D3B8837167D6` FOREIGN KEY (`donation_event_id`) REFERENCES `food_donation_event` (`donation_event_id`) ON DELETE CASCADE;

--
-- Constraints for table `food_donation_event`
--
ALTER TABLE `food_donation_event`
  ADD CONSTRAINT `fk_events_delivery` FOREIGN KEY (`delivery_id`) REFERENCES `delivery` (`delivery_id`) ON DELETE SET NULL;

--
-- Constraints for table `food_donation_items`
--
ALTER TABLE `food_donation_items`
  ADD CONSTRAINT `fk_items_dish` FOREIGN KEY (`item_id`) REFERENCES `dish` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_items_event` FOREIGN KEY (`donation_event_id`) REFERENCES `food_donation_event` (`donation_event_id`) ON DELETE CASCADE;

--
-- Constraints for table `gps_log`
--
ALTER TABLE `gps_log`
  ADD CONSTRAINT `FK_GPS_LOG_CAR` FOREIGN KEY (`car_id`) REFERENCES `fleet_car` (`car_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_GPS_LOG_DELIVERY_MAN` FOREIGN KEY (`delivery_man_id`) REFERENCES `delivery_man` (`delivery_man_id`) ON DELETE SET NULL;

--
-- Constraints for table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `FK_NOTIFICATION_RECIPIENT` FOREIGN KEY (`recipient_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `FK_E52FFDEEB83297E7` FOREIGN KEY (`reservation_id`) REFERENCES `reservation` (`reservation_id`) ON DELETE SET NULL;

--
-- Constraints for table `password_reset_token`
--
ALTER TABLE `password_reset_token`
  ADD CONSTRAINT `FK_5A6E2B3EA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `FK_CEB607C9BABCF7FB` FOREIGN KEY (`donation_event_id`) REFERENCES `food_donation_event` (`donation_event_id`) ON DELETE CASCADE;

--
-- Constraints for table `reservation`
--
ALTER TABLE `reservation`
  ADD CONSTRAINT `FK_42C84955ECFF285C` FOREIGN KEY (`table_id`) REFERENCES `restaurant_table` (`table_id`);

--
-- Constraints for table `sustainability_metrics`
--
ALTER TABLE `sustainability_metrics`
  ADD CONSTRAINT `fk_metrics_event` FOREIGN KEY (`donation_event_id`) REFERENCES `food_donation_event` (`donation_event_id`) ON DELETE CASCADE;

--
-- Constraints for table `webauthn_credential`
--
ALTER TABLE `webauthn_credential`
  ADD CONSTRAINT `FK_WEBAUTHN_USER_ID` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
