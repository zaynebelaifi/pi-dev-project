-- Safe merge script: old database -> includes new schema parts
-- Target DB: project
-- This script does not drop tables and does not delete existing data.

START TRANSACTION;

USE project;

-- 1) Existing table upgrades
ALTER TABLE delivery
  ADD COLUMN IF NOT EXISTS cart_items LONGTEXT DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS order_total DECIMAL(10,2) DEFAULT NULL;

ALTER TABLE menu
  ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1;

-- Keep menu flags aligned when both columns exist
UPDATE menu SET is_active = isActive WHERE is_active <> isActive;

-- 2) New tables from the new schema (created only if missing)
CREATE TABLE IF NOT EXISTS author (
  id INT(11) NOT NULL AUTO_INCREMENT,
  username VARCHAR(55) DEFAULT NULL,
  email VARCHAR(55) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS book (
  id INT(11) NOT NULL AUTO_INCREMENT,
  author_id INT(11) NOT NULL,
  title VARCHAR(55) NOT NULL,
  publication_date DATE NOT NULL,
  enabled TINYINT(1) NOT NULL,
  PRIMARY KEY (id),
  KEY IDX_CBE5A331F675F31B (author_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS doctrine_migration_versions (
  version VARCHAR(191) NOT NULL,
  executed_at DATETIME DEFAULT NULL,
  execution_time INT(11) DEFAULT NULL,
  PRIMARY KEY (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS messenger_messages (
  id BIGINT(20) NOT NULL AUTO_INCREMENT,
  body LONGTEXT NOT NULL,
  headers LONGTEXT NOT NULL,
  queue_name VARCHAR(190) NOT NULL,
  created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  available_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  delivered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (id),
  KEY IDX_75EA56E0FB7336F0 (queue_name),
  KEY IDX_75EA56E0E3BD61CE (available_at),
  KEY IDX_75EA56E016BA31DB (delivered_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS restaurant_table (
  table_id INT(11) NOT NULL AUTO_INCREMENT,
  capacity INT(11) NOT NULL,
  status ENUM('AVAILABLE','RESERVED') NOT NULL DEFAULT 'AVAILABLE',
  PRIMARY KEY (table_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reservation (
  reservation_id INT(11) NOT NULL AUTO_INCREMENT,
  client_id INT(11) NOT NULL,
  table_id INT(11) NOT NULL,
  reservation_date DATE NOT NULL,
  reservation_time TIME NOT NULL,
  number_of_guests INT(11) NOT NULL,
  status ENUM('CONFIRMED','CANCELLED') NOT NULL DEFAULT 'CONFIRMED',
  PRIMARY KEY (reservation_id),
  KEY client_id (client_id),
  KEY table_id (table_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
  order_id INT(11) NOT NULL AUTO_INCREMENT,
  client_id INT(11) NOT NULL,
  reservation_id INT(11) DEFAULT NULL,
  order_type ENUM('DINE_IN','DELIVERY') NOT NULL,
  order_date DATETIME NOT NULL,
  delivery_address VARCHAR(200) DEFAULT NULL,
  status ENUM('PENDING','PREPARED','DELIVERED') DEFAULT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (order_id),
  KEY client_id (client_id),
  KEY reservation_id (reservation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) Add foreign keys only if missing
SET @fk_exists = (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'FK_CBE5A331F675F31B'
);
SET @sql = IF(
  @fk_exists = 0,
  'ALTER TABLE book ADD CONSTRAINT FK_CBE5A331F675F31B FOREIGN KEY (author_id) REFERENCES author (id)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_res_client'
);
SET @sql = IF(
  @fk_exists = 0,
  'ALTER TABLE reservation ADD CONSTRAINT fk_res_client FOREIGN KEY (client_id) REFERENCES user1 (id) ON UPDATE CASCADE',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_res_table'
);
SET @sql = IF(
  @fk_exists = 0,
  'ALTER TABLE reservation ADD CONSTRAINT fk_res_table FOREIGN KEY (table_id) REFERENCES restaurant_table (table_id) ON UPDATE CASCADE',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_ord_client'
);
SET @sql = IF(
  @fk_exists = 0,
  'ALTER TABLE orders ADD CONSTRAINT fk_ord_client FOREIGN KEY (client_id) REFERENCES user1 (id) ON UPDATE CASCADE',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_ord_reservation'
);
SET @sql = IF(
  @fk_exists = 0,
  'ALTER TABLE orders ADD CONSTRAINT fk_ord_reservation FOREIGN KEY (reservation_id) REFERENCES reservation (reservation_id) ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4) Mark existing project migrations as executed (safe, no duplicates)
INSERT IGNORE INTO doctrine_migration_versions (version, executed_at, execution_time) VALUES
('DoctrineMigrations\\Version20250930134306', NOW(), 0),
('DoctrineMigrations\\Version20251014123441', NOW(), 0),
('DoctrineMigrations\\Version20260407120000', NOW(), 0);

COMMIT;
