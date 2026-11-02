-- HouGarden full deploy (schema + data). Import in phpMyAdmin (database already selected).
-- Generated: 2026-11-03T09:55:11+11:00
-- Properties: 33
-- No admin user — create via admin/setup.php after import.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- HouGarden / houdwellingarden.com — CANONICAL DATABASE SCHEMA (single file for new installs)
-- MySQL 8.0+ / MariaDB 10.4+ recommended. No seeded admin passwords.
--
-- phpMyAdmin (InfinityFree / production): import database/hougarden-deploy.sql — NOT this file alone.
-- This file is structure-only for developers. hougarden-deploy.sql = schema + 33 AU listings + data.
--
-- Upgrading an OLD database: do NOT re-import this file (it DROPs tables). Deploy new PHP code;
-- open Admin once — includes/schema-migrate.php applies pending steps (see settings.schema_version).
--
-- Legacy SQL files live in database/archive/legacy/ (reference only; merged here + PHP migrations).

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `property_amenity`;
DROP TABLE IF EXISTS `property_images`;
DROP TABLE IF EXISTS `inquiries`;
DROP TABLE IF EXISTS `properties`;
DROP TABLE IF EXISTS `amenities`;
DROP TABLE IF EXISTS `property_types`;
DROP TABLE IF EXISTS `regions`;
DROP TABLE IF EXISTS `agents`;
DROP TABLE IF EXISTS `offices`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------------
-- Users (admins). Password is created via one-time setup (Phase 2), never seeded.
-- ---------------------------------------------------------------------------
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(191) NOT NULL,
  `password_hash` VARCHAR(255) NULL DEFAULT NULL COMMENT 'NULL until one-time admin setup completes',
  `name` VARCHAR(120) NOT NULL DEFAULT '',
  `role` ENUM('admin') NOT NULL DEFAULT 'admin',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `setup_completed_at` DATETIME NULL DEFAULT NULL,
  `last_login_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Agents (public experts / listing agents)
-- ---------------------------------------------------------------------------
CREATE TABLE `agents` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(160) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `title` VARCHAR(160) NOT NULL DEFAULT '',
  `region` VARCHAR(120) NOT NULL DEFAULT '',
  `bio` TEXT NULL,
  `photo_path` VARCHAR(500) NULL DEFAULT NULL,
  `badge` VARCHAR(80) NULL DEFAULT NULL,
  `email` VARCHAR(191) NULL DEFAULT NULL,
  `phone` VARCHAR(40) NULL DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_agents_slug` (`slug`),
  KEY `idx_agents_active_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Property types
-- ---------------------------------------------------------------------------
CREATE TABLE `property_types` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(80) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_property_types_slug` (`slug`),
  KEY `idx_property_types_active` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Regions (destinations used on listings / filters)
-- ---------------------------------------------------------------------------
CREATE TABLE `regions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(80) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `image_path` VARCHAR(500) NULL DEFAULT NULL,
  `home_filter_key` VARCHAR(16) NULL DEFAULT NULL COMMENT 'location|market — homepage Locations filter',
  `home_filter_value` VARCHAR(120) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_regions_slug` (`slug`),
  KEY `idx_regions_active` (`is_active`, `sort_order`),
  KEY `idx_regions_featured` (`is_featured`, `is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Properties (single source of truth)
-- ---------------------------------------------------------------------------
CREATE TABLE `properties` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(191) NOT NULL,
  `reference_code` VARCHAR(64) NOT NULL,
  `mls_number` VARCHAR(64) NULL DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` MEDIUMTEXT NULL,
  `property_type_id` INT UNSIGNED NULL DEFAULT NULL,
  `listing_purpose` ENUM('sale', 'rent', 'lease') NOT NULL DEFAULT 'sale',
  `status` ENUM(
    'draft',
    'available',
    'pending',
    'under_contract',
    'sold',
    'private',
    'archived'
  ) NOT NULL DEFAULT 'draft',
  `price` DECIMAL(15, 2) NULL DEFAULT NULL,
  `price_on_request` TINYINT(1) NOT NULL DEFAULT 0,
  `currency` CHAR(3) NOT NULL DEFAULT 'AUD',
  `address_line` VARCHAR(255) NOT NULL DEFAULT '',
  `city` VARCHAR(120) NOT NULL DEFAULT '',
  `region` VARCHAR(120) NOT NULL DEFAULT '',
  `state` VARCHAR(80) NOT NULL DEFAULT 'NSW',
  `postal_code` VARCHAR(20) NOT NULL DEFAULT '',
  `country` VARCHAR(80) NOT NULL DEFAULT 'Australia',
  `bedrooms` DECIMAL(4, 1) NULL DEFAULT NULL,
  `bathrooms` DECIMAL(4, 1) NULL DEFAULT NULL,
  `sqft` INT UNSIGNED NULL DEFAULT NULL,
  `lot_acres` DECIMAL(10, 2) NULL DEFAULT NULL,
  `year_built` SMALLINT UNSIGNED NULL DEFAULT NULL,
  `badge` VARCHAR(80) NULL DEFAULT NULL COMMENT 'e.g. Just Listed, Exclusive Listing',
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `is_home_recommend` TINYINT(1) NOT NULL DEFAULT 0,
  `is_home_watched` TINYINT(1) NOT NULL DEFAULT 0,
  `is_home_saved` TINYINT(1) NOT NULL DEFAULT 0,
  `agent_id` INT UNSIGNED NULL DEFAULT NULL,
  `agent_quote` TEXT NULL DEFAULT NULL COMMENT 'Per-listing quote on property detail sticky agent card',
  `listed_at` DATE NULL DEFAULT NULL,
  `source_name` VARCHAR(80) NULL DEFAULT NULL COMMENT 'Internal import source e.g. century_communities',
  `source_url` VARCHAR(500) NULL DEFAULT NULL COMMENT 'Parent listing URL (admin/dev only)',
  `source_reference` VARCHAR(120) NULL DEFAULT NULL COMMENT 'Stable source key e.g. plan slug',
  `created_by` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_properties_slug` (`slug`),
  UNIQUE KEY `uq_properties_reference_code` (`reference_code`),
  UNIQUE KEY `uq_properties_mls_number` (`mls_number`),
  KEY `idx_properties_status` (`status`),
  KEY `idx_properties_region` (`region`),
  KEY `idx_properties_price` (`price`),
  KEY `idx_properties_beds` (`bedrooms`),
  KEY `idx_properties_featured` (`is_featured`),
  KEY `idx_properties_created` (`created_at`),
  KEY `idx_properties_city` (`city`),
  KEY `idx_properties_address_city` (`address_line`(100), `city`),
  KEY `idx_properties_source` (`source_name`, `source_reference`),
  CONSTRAINT `fk_properties_type`
    FOREIGN KEY (`property_type_id`) REFERENCES `property_types` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_properties_agent`
    FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_properties_created_by`
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Property images (ordered gallery; one cover enforced in application logic — Phase 3)
-- Note: store mls_number as NULL (not '') to avoid UNIQUE collisions on empty strings.
-- ---------------------------------------------------------------------------
CREATE TABLE `property_images` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `property_id` INT UNSIGNED NOT NULL,
  `path` VARCHAR(500) NOT NULL COMMENT 'Relative upload path under uploads/ (or absolute https URL if used)',
  `alt_text` VARCHAR(255) NULL DEFAULT NULL,
  `caption` VARCHAR(160) NULL DEFAULT NULL COMMENT 'Room/label overlay e.g. GREAT ROOM (Glass House gallery)',
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_cover` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_property_images_property` (`property_id`),
  KEY `idx_property_images_cover` (`property_id`, `is_cover`),
  KEY `idx_property_images_sort` (`property_id`, `sort_order`),
  CONSTRAINT `fk_property_images_property`
    FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Amenities + pivot
-- ---------------------------------------------------------------------------
CREATE TABLE `amenities` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(80) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `category` ENUM('interior', 'exterior', 'community', 'other') NOT NULL DEFAULT 'other',
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_amenities_slug` (`slug`),
  KEY `idx_amenities_active` (`is_active`, `category`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `property_amenity` (
  `property_id` INT UNSIGNED NOT NULL,
  `amenity_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`property_id`, `amenity_id`),
  CONSTRAINT `fk_property_amenity_property`
    FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_property_amenity_amenity`
    FOREIGN KEY (`amenity_id`) REFERENCES `amenities` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Inquiries (contact + property)
-- ---------------------------------------------------------------------------
CREATE TABLE `inquiries` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` ENUM('contact', 'property_inquiry') NOT NULL DEFAULT 'contact',
  `status` ENUM('new', 'in_progress', 'resolved') NOT NULL DEFAULT 'new',
  `property_id` INT UNSIGNED NULL DEFAULT NULL,
  `first_name` VARCHAR(80) NOT NULL DEFAULT '',
  `last_name` VARCHAR(80) NOT NULL DEFAULT '',
  `email` VARCHAR(191) NOT NULL,
  `phone` VARCHAR(40) NULL DEFAULT NULL,
  `interest` VARCHAR(120) NULL DEFAULT NULL,
  `message` TEXT NOT NULL,
  `admin_notes` TEXT NULL,
  `assigned_to` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inquiries_status` (`status`),
  KEY `idx_inquiries_type` (`type`),
  KEY `idx_inquiries_created` (`created_at`),
  CONSTRAINT `fk_inquiries_property`
    FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_inquiries_assigned`
    FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Offices (contact page)
-- ---------------------------------------------------------------------------
CREATE TABLE `offices` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(160) NOT NULL,
  `address_line` VARCHAR(255) NOT NULL DEFAULT '',
  `city` VARCHAR(120) NOT NULL DEFAULT '',
  `region` VARCHAR(120) NOT NULL DEFAULT '',
  `postal_code` VARCHAR(20) NOT NULL DEFAULT '',
  `phone` VARCHAR(40) NULL DEFAULT NULL,
  `email` VARCHAR(191) NULL DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Settings (key/value)
-- ---------------------------------------------------------------------------
CREATE TABLE `settings` (
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




-- ========== DATA (HouGarden AU) ==========

DELETE FROM `property_types`;
INSERT INTO `property_types` (`id`, `slug`, `name`, `sort_order`, `is_active`) VALUES
(1, 'estate', 'Estate', 10, 0),
(2, 'condo', 'Apartment', 20, 1),
(3, 'ranch', 'Ranch', 30, 0),
(4, 'chalet', 'Chalet', 40, 0),
(5, 'single_family', 'House', 50, 1),
(6, 'land', 'Land', 60, 1),
(9, 'townhouse', 'Townhouse', 30, 1);

DELETE FROM `regions`;
INSERT INTO `regions` (`id`, `slug`, `name`, `sort_order`, `is_active`, `is_featured`, `image_path`, `home_filter_key`, `home_filter_value`) VALUES
(13, 'bellevue-hill', 'Bellevue Hill', 10, 1, 1, 'https://images.unsplash.com/photo-1506973035872-a4ec16b8e8d9?auto=format&fit=crop&w=800&q=80', 'location', 'Bellevue Hill'),
(14, 'rose-bay', 'Rose Bay', 20, 1, 1, 'https://images.unsplash.com/photo-1505142468610-359e7d316be0?auto=format&fit=crop&w=800&q=80', 'location', 'Rose Bay'),
(15, 'south-yarra', 'South Yarra', 30, 1, 1, 'https://images.unsplash.com/photo-1514395462725-fb8496db94a8?auto=format&fit=crop&w=800&q=80', 'location', 'South Yarra'),
(16, 'ocean-grove', 'Ocean Grove', 40, 1, 1, 'https://images.unsplash.com/photo-1499793983690-e29da59ef1c2?auto=format&fit=crop&w=800&q=80', 'location', 'Ocean Grove'),
(17, 'victoria', 'Victoria', 50, 1, 1, 'https://images.unsplash.com/photo-1519864607624-a07bb8c2477e?auto=format&fit=crop&w=800&q=80', 'market', 'victoria');

DELETE FROM `amenities`;
INSERT INTO `amenities` (`id`, `slug`, `name`, `category`, `sort_order`, `is_active`) VALUES
(1, 'smart-home', 'Smart Home Tech', 'interior', 10, 1),
(2, 'wine-cellar', 'Wine Cellar', 'interior', 20, 1),
(3, 'home-theater', 'Home Theater', 'interior', 30, 1),
(4, 'ski-in-ski-out', 'Ski-In/Ski-Out', 'exterior', 40, 1),
(5, 'heated-driveway', 'Heated Driveway', 'exterior', 50, 1),
(6, 'outdoor-kitchen', 'Outdoor Kitchen', 'exterior', 60, 1),
(7, 'gated-access', 'Gated Access', 'community', 70, 1),
(8, 'clubhouse', 'Clubhouse', 'community', 80, 1);

DELETE FROM `agents`;
INSERT INTO `agents` (`id`, `slug`, `name`, `title`, `region`, `bio`, `photo_path`, `badge`, `email`, `phone`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'eleanor-vance', 'Eleanor Vance', 'Managing Broker', 'Aspen Core', 'With over two decades navigating Aspen\'s complex luxury market, Eleanor provides unmatched strategic advisory for ultra-high-net-worth clientele seeking legacy properties.', 'assets/img/agent-eleanor-vance.jpg', 'Top Producer', NULL, NULL, 1, 10, '2026-11-01 02:17:46', '2026-11-01 02:17:46'),
(2, 'julian-thorne', 'Julian Thorne', 'Global Luxury Specialist', 'Vail', 'Specializing in ski-in/ski-out estates and private ranches. Julian\'s international network connects global buyers with Colorado\'s most exclusive alpine retreats.', 'assets/img/agent-julian-thorne.jpg', NULL, NULL, NULL, 1, 20, '2026-11-01 02:17:46', '2026-11-01 02:17:46'),
(3, 'chloe-sterling', 'Chloe Sterling', 'Principal Agent', 'Denver Metro', 'Chloe brings a data-driven approach to Denver\'s high-end urban market, focusing on luxury penthouses and historic Cherry Creek estates with unparalleled precision.', 'assets/img/agent-chloe-sterling.jpg', NULL, NULL, NULL, 1, 30, '2026-11-01 02:17:46', '2026-11-01 02:17:46'),
(4, 'marcus-wright', 'Marcus Wright', 'Ranch & Land Director', 'Telluride', 'The definitive authority on large-scale Colorado land acquisitions. Marcus specializes in sporting properties, equestrian estates, and conservation easements.', 'assets/img/agent-marcus-wright.jpg', NULL, NULL, NULL, 1, 40, '2026-11-01 02:17:46', '2026-11-01 02:17:46');

DELETE FROM `offices`;
INSERT INTO `offices` (`id`, `name`, `address_line`, `city`, `region`, `postal_code`, `phone`, `email`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Vail Village', '', 'Vail', 'Vail Valley', '', NULL, NULL, 10, 1, '2026-11-01 02:17:46', '2026-11-01 02:17:46'),
(2, 'Beaver Creek', '', 'Beaver Creek', 'Vail Valley', '', NULL, NULL, 20, 1, '2026-11-01 02:17:46', '2026-11-01 02:17:46'),
(3, 'Aspen Core', '', 'Aspen', 'Roaring Fork', '', NULL, NULL, 30, 1, '2026-11-01 02:17:46', '2026-11-01 02:17:46'),
(4, 'Denver Cherry Creek', '', 'Denver', 'Denver Metro', '', NULL, NULL, 40, 1, '2026-11-01 02:17:46', '2026-11-01 02:17:46');

DELETE FROM `settings`;
INSERT INTO `settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('mail_from_name', NULL, '2026-11-01 02:17:46'),
('schema_version', '8', '2026-11-02 23:52:26'),
('site_email', 'hello@houdwellingarden.com', '2026-11-01 02:17:59'),
('site_favicon_path', NULL, '2026-11-01 02:17:46'),
('site_logo_path', NULL, '2026-11-01 02:17:46'),
('site_name', 'Houdwell Garden', '2026-11-01 02:17:59'),
('site_phone', '800.555.0123', '2026-11-01 02:17:46');

DELETE FROM `properties`;
INSERT INTO `properties` (`id`, `slug`, `reference_code`, `mls_number`, `title`, `description`, `property_type_id`, `listing_purpose`, `status`, `price`, `price_on_request`, `currency`, `address_line`, `city`, `region`, `state`, `postal_code`, `country`, `bedrooms`, `bathrooms`, `sqft`, `lot_acres`, `year_built`, `badge`, `is_featured`, `is_home_saved`, `is_home_watched`, `is_home_recommend`, `agent_id`, `agent_quote`, `listed_at`, `source_name`, `source_url`, `source_reference`, `created_by`, `created_at`, `updated_at`) VALUES
(1, '1-58-bellevue-road-bellevue-hill-1', 'HG-AU-001', NULL, 'Whole-floor garden residence, Bellevue Road', NULL, 5, 'sale', 'available', '6800000.00', 0, 'AUD', '1/58 Bellevue Road', 'Bellevue Hill', 'Eastern Suburbs', 'NSW', '2023', 'Australia', '3.0', '3.0', 5619, NULL, NULL, 'Just Listed', 1, 0, 0, 1, 1, NULL, '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/search/buy/list_in_suburb-bellevue-hill-11110142_page-1', 'HG-AU-001', NULL, '2026-11-01 02:17:46', '2026-11-01 17:09:43'),
(2, '1-206b-victoria-road-bellevue-hill-2', 'HG-AU-002', NULL, 'Otium garden residence, Victoria Road', NULL, 5, 'sale', 'available', '8100000.00', 0, 'AUD', '1/206B Victoria Road', 'Bellevue Hill', 'Eastern Suburbs', 'NSW', '2023', 'Australia', '3.0', '2.0', 4300, NULL, NULL, 'Exclusive', 1, 0, 0, 1, 2, NULL, '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/search/buy/list_in_suburb-bellevue-hill-11110142_page-1', 'HG-AU-002', NULL, '2026-11-01 02:17:46', '2026-11-01 17:09:43'),
(3, '3-112-victoria-road-bellevue-hill-3', 'HG-AU-003', NULL, 'Harbour-side apartment, Victoria Road', NULL, 2, 'sale', 'available', '2450000.00', 0, 'AUD', '3/112 Victoria Road', 'Bellevue Hill', 'Eastern Suburbs', 'NSW', '2023', 'Australia', '2.0', '2.0', 1200, NULL, NULL, NULL, 0, 0, 0, 0, 2, NULL, '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/search/buy/list_in_suburb-bellevue-hill-11110142_page-1', 'HG-AU-003', NULL, '2026-11-01 02:17:46', '2026-11-01 17:09:43'),
(4, '12-18-bellevue-road-bellevue-hill-4', 'HG-AU-004', NULL, 'Cooper Park outlook, Bellevue Road', NULL, 5, 'sale', 'available', '3200000.00', 0, 'AUD', '12/18 Bellevue Road', 'Bellevue Hill', 'Eastern Suburbs', 'NSW', '2023', 'Australia', '3.0', '2.0', 1850, NULL, NULL, 'Open Home', 0, 0, 0, 0, 1, NULL, '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/search/buy/list_in_suburb-bellevue-hill-11110142_page-1', 'HG-AU-004', NULL, '2026-11-01 02:17:46', '2026-11-01 17:09:43'),
(5, '5-crana-avenue-bellevue-hill-5', 'HG-AU-005', NULL, 'Village proximity, Crana Avenue', NULL, 5, 'sale', 'available', '8900000.00', 0, 'AUD', '5 Crana Avenue', 'Bellevue Hill', 'Eastern Suburbs', 'NSW', '2023', 'Australia', '4.0', '3.0', 3200, NULL, NULL, NULL, 0, 0, 0, 0, 2, NULL, '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/search/buy/list_in_suburb-bellevue-hill-11110142_page-1', 'HG-AU-005', NULL, '2026-11-01 02:17:46', '2026-11-01 17:09:43'),
(6, '2-45-bellevue-road-bellevue-hill-6', 'HG-AU-006', NULL, 'Designer terrace, Bellevue Road', NULL, 5, 'sale', 'available', '4100000.00', 0, 'AUD', '2/45 Bellevue Road', 'Bellevue Hill', 'Eastern Suburbs', 'NSW', '2023', 'Australia', '3.0', '2.0', 2100, NULL, NULL, NULL, 0, 0, 0, 0, 4, NULL, '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/search/buy/list_in_suburb-bellevue-hill-11110142_page-1', 'HG-AU-006', NULL, '2026-11-01 02:17:46', '2026-11-01 17:09:43'),
(7, '1-22-spencer-street-rose-bay-7', 'HG-AU-007', NULL, 'Whole-floor garden apartment, Spencer Street', NULL, 5, 'sale', 'available', '3675000.00', 0, 'AUD', '1/22 Spencer Street', 'Rose Bay', 'Eastern Suburbs', 'NSW', '2029', 'Australia', '3.0', '3.0', 4300, '4.50', NULL, 'Just Listed', 1, 0, 0, 1, 1, NULL, '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/search/buy/list_in_suburb-rose-bay-11110158_page-1', 'HG-AU-007', NULL, '2026-11-01 02:17:46', '2026-11-01 17:09:43'),
(8, '1-4-albemarle-avenue-rose-bay-8', 'HG-AU-008', NULL, 'Strana whole-floor apartment, Albemarle Avenue', NULL, 5, 'sale', 'available', '3150000.00', 0, 'AUD', '1/4 Albemarle Avenue', 'Rose Bay', 'Eastern Suburbs', 'NSW', '2029', 'Australia', '3.0', '2.0', 4280, '5.40', NULL, NULL, 1, 0, 0, 1, 1, 'This residence represents the pinnacle of Red Mountain architecture ? light, scale, and privacy in equal measure.', '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/search/buy/list_in_suburb-rose-bay-11110158_page-1', 'HG-AU-008', NULL, '2026-11-01 02:17:46', '2026-11-01 17:09:43'),
(9, '8-30-32-liverpool-street-rose-bay-9', 'HG-AU-009', NULL, 'Harbourside apartment, Old South Head Road', NULL, 2, 'sale', 'available', '2850000.00', 0, 'AUD', '8/30-32 Liverpool Street', 'Rose Bay', 'Eastern Suburbs', 'NSW', '2029', 'Australia', '2.0', '2.0', 1100, NULL, NULL, 'Auction', 0, 0, 0, 0, 1, NULL, '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/search/buy/list_in_suburb-rose-bay-11110158_page-1', 'HG-AU-009', NULL, '2026-11-01 02:17:47', '2026-11-01 17:09:43'),
(10, '2-27-balfour-road-rose-bay-10', 'HG-AU-010', NULL, 'Renovated art-deco, Balfour Road', NULL, 2, 'sale', 'available', '1950000.00', 0, 'AUD', '2/27 Balfour Road', 'Rose Bay', 'Eastern Suburbs', 'NSW', '2029', 'Australia', '2.0', '1.0', 950, NULL, NULL, NULL, 0, 0, 0, 0, 2, NULL, '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/search/buy/list_in_suburb-rose-bay-11110158_page-1', 'HG-AU-010', NULL, '2026-11-01 02:17:47', '2026-11-01 17:09:43'),
(11, '6-20-plumer-road-rose-bay-11', 'HG-AU-011', NULL, 'Garden apartment, Plumer Road', 'Listing in Albert Park, Victoria. Demo Victoria market for HouGarden-style site.', 2, 'sale', 'available', '1650000.00', 0, 'AUD', '6/20 Plumer Road', 'Rose Bay', 'Eastern Suburbs', 'NSW', '2029', 'Australia', '2.0', '1.0', 900, NULL, NULL, NULL, 0, 0, 0, 0, 3, NULL, '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/search/buy/list_in_suburb-rose-bay-11110158_page-1', 'HG-AU-011', NULL, '2026-11-01 02:17:47', '2026-11-01 22:54:59'),
(12, 'bg04-575-old-south-head-road-rose-bay-12', 'HG-AU-012', NULL, 'North-facing garden apartment, Old South Head Road', 'Listing in Docklands, Victoria. Demo Victoria market for HouGarden-style site.', 5, 'sale', 'available', '4200000.00', 0, 'AUD', 'BG04/575 Old South Head Road', 'Rose Bay', 'Eastern Suburbs', 'NSW', '2029', 'Australia', '3.0', '2.0', 1600, NULL, NULL, 'New', 0, 0, 0, 0, 4, NULL, '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/search/buy/list_in_suburb-rose-bay-11110158_page-1', 'HG-AU-012', NULL, '2026-11-01 02:17:47', '2026-11-01 22:55:02'),
(13, '411-35-wilson-street-south-yarra-13', 'HG-AU-013', NULL, 'Unit 411/35 Wilson Street', NULL, 2, 'sale', 'available', '530000.00', 0, 'AUD', '411/35 Wilson Street', 'South Yarra', 'Melbourne Inner', 'VIC', '3141', 'Australia', '2.0', '1.0', 850, NULL, NULL, 'Sold', 0, 0, 0, 0, 1, NULL, '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/property/vic/south-yarra-3141/unit-411-35-wilson-street/O4LFR', 'HG-AU-013', NULL, '2026-11-01 02:17:47', '2026-11-01 17:09:43'),
(14, '1203-55-commercial-road-south-yarra-14', 'HG-AU-014', NULL, 'Toorak Road precinct apartment', NULL, 2, 'sale', 'available', '890000.00', 0, 'AUD', '1203/55 Commercial Road', 'South Yarra', 'Melbourne Inner', 'VIC', '3141', 'Australia', '2.0', '2.0', 980, NULL, NULL, 'Just Listed', 1, 0, 0, 1, 2, NULL, '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/search/buy/list_in_suburb-south-yarra-11110199_page-1', 'HG-AU-014', NULL, '2026-11-01 02:17:47', '2026-11-01 17:09:43'),
(15, '3-18-domain-road-south-yarra-15', 'HG-AU-015', NULL, 'Yarra-side residence, Domain Road', NULL, 5, 'sale', 'available', '2450000.00', 0, 'AUD', '3/18 Domain Road', 'South Yarra', 'Melbourne Inner', 'VIC', '3141', 'Australia', '3.0', '2.0', 1750, NULL, NULL, NULL, 1, 0, 0, 1, 3, NULL, '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/search/buy/list_in_suburb-south-yarra-11110199_page-1', 'HG-AU-015', NULL, '2026-11-01 02:17:47', '2026-11-01 17:09:43'),
(16, '5-12-arthur-street-south-yarra-16', 'HG-AU-016', NULL, 'Chapel Street lifestyle, Arthur Street', NULL, 2, 'sale', 'available', '720000.00', 0, 'AUD', '5/12 Arthur Street', 'South Yarra', 'Melbourne Inner', 'VIC', '3141', 'Australia', '1.0', '1.0', 650, NULL, NULL, NULL, 0, 0, 0, 0, 4, NULL, '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/search/buy/list_in_suburb-south-yarra-11110199_page-1', 'HG-AU-016', NULL, '2026-11-01 02:17:47', '2026-11-01 17:09:43'),
(17, '8-45-anderson-street-south-yarra-17', 'HG-AU-017', NULL, 'Botanic Gardens outlook, Anderson Street', NULL, 5, 'sale', 'available', '3100000.00', 0, 'AUD', '8/45 Anderson Street', 'South Yarra', 'Melbourne Inner', 'VIC', '3141', 'Australia', '3.0', '2.0', 1900, NULL, NULL, 'Open Home', 0, 0, 0, 0, 1, NULL, '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/search/buy/list_in_suburb-south-yarra-11110199_page-1', 'HG-AU-017', NULL, '2026-11-01 02:17:47', '2026-11-01 17:09:43'),
(18, '22-fawkner-street-south-yarra-18', 'HG-AU-018', NULL, 'Modern terrace, Fawkner Street', NULL, 5, 'sale', 'available', '1850000.00', 0, 'AUD', '22 Fawkner Street', 'South Yarra', 'Melbourne Inner', 'VIC', '3141', 'Australia', '3.0', '2.0', 1600, NULL, NULL, NULL, 0, 0, 0, 0, 2, NULL, '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/search/buy/list_in_suburb-south-yarra-11110199_page-1', 'HG-AU-018', NULL, '2026-11-01 02:17:47', '2026-11-01 17:09:43'),
(19, '94-sunset-strip-ocean-grove-19', 'HG-AU-019', NULL, 'Coastal family home, Sunset Strip', NULL, 5, 'sale', 'available', '742500.00', 0, 'AUD', '94 Sunset Strip', 'Ocean Grove', 'Bellarine Peninsula', 'VIC', '3226', 'Australia', '3.0', '1.0', 1200, NULL, NULL, 'Just Listed', 1, 0, 0, 1, 3, NULL, '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/search/buy/list_in_suburb-ocean-grove-11110288_page-1', 'HG-AU-019', NULL, '2026-11-01 02:17:47', '2026-11-01 17:09:43'),
(20, '11-sunbeam-street-ocean-grove-20', 'HG-AU-020', NULL, 'Beachside living, Sunbeam Street', NULL, 5, 'sale', 'available', '753500.00', 0, 'AUD', '11 Sunbeam Street', 'Ocean Grove', 'Bellarine Peninsula', 'VIC', '3226', 'Australia', '3.0', '2.0', 1100, NULL, NULL, NULL, 0, 0, 0, 0, 4, NULL, '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/search/buy/list_in_suburb-ocean-grove-11110288_page-1', 'HG-AU-020', NULL, '2026-11-01 02:17:47', '2026-11-01 17:09:43'),
(21, '18-bonnyvale-road-ocean-grove-21', 'HG-AU-021', NULL, 'Elevated coastal home, Bonnyvale Road', NULL, 5, 'sale', 'available', '1320000.00', 0, 'AUD', '18 Bonnyvale Road', 'Ocean Grove', 'Bellarine Peninsula', 'VIC', '3226', 'Australia', '4.0', '2.0', 2200, NULL, NULL, NULL, 1, 0, 0, 1, 1, NULL, '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/search/buy/list_in_suburb-ocean-grove-11110288_page-1', 'HG-AU-021', NULL, '2026-11-01 02:17:47', '2026-11-01 17:09:43'),
(22, '7-parkside-boulevard-ocean-grove-22', 'HG-AU-022', NULL, 'Family home, Parkside Boulevard', NULL, 5, 'sale', 'available', '1157500.00', 0, 'AUD', '7 Parkside Boulevard', 'Ocean Grove', 'Bellarine Peninsula', 'VIC', '3226', 'Australia', '5.0', '3.0', 2400, NULL, NULL, 'Open Home', 0, 1, 0, 0, 2, NULL, '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/search/buy/list_in_suburb-ocean-grove-11110288_page-1', 'HG-AU-022', NULL, '2026-11-01 02:17:47', '2026-11-01 17:09:43'),
(23, '90-shorebreak-way-ocean-grove-23', 'HG-AU-023', NULL, 'Near the beach, Shorebreak Way', NULL, 5, 'sale', 'available', '574000.00', 0, 'AUD', '90 Shorebreak Way', 'Ocean Grove', 'Bellarine Peninsula', 'VIC', '3226', 'Australia', NULL, NULL, NULL, NULL, NULL, 'Land', 0, 1, 0, 0, 3, NULL, '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/search/buy/list_in_suburb-ocean-grove-11110288_page-1', 'HG-AU-023', NULL, '2026-11-01 02:17:47', '2026-11-01 17:09:43'),
(24, '35-aldebaran-road-ocean-grove-24', 'HG-AU-024', NULL, 'Aldebaran Road family home', NULL, 5, 'sale', 'available', '1630000.00', 0, 'AUD', '35 Aldebaran Road', 'Ocean Grove', 'Bellarine Peninsula', 'VIC', '3226', 'Australia', '4.0', '3.0', 2100, NULL, NULL, NULL, 0, 1, 0, 0, 4, NULL, '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/search/buy/list_in_suburb-ocean-grove-11110288_page-1', 'HG-AU-024', NULL, '2026-11-01 02:17:47', '2026-11-01 17:09:43'),
(25, '8-22-bellevue-road-bellevue-hill-25', 'HG-AU-025', NULL, 'Penthouse with district views, Bellevue Road', 'Listing in Geelong West, Victoria. Demo Victoria market for HouGarden-style site.', 5, 'sale', 'available', '4950000.00', 0, 'AUD', '8/22 Bellevue Road', 'Bellevue Hill', 'Eastern Suburbs', 'NSW', '2023', 'Australia', '3.0', '2.0', 2100, NULL, NULL, NULL, 0, 0, 1, 0, 1, NULL, '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/search/buy/list_in_suburb-bellevue-hill-11110142_page-1', 'HG-AU-025', NULL, '2026-11-01 02:17:47', '2026-11-01 22:55:27'),
(26, '2-88-victoria-road-bellevue-hill-26', 'HG-AU-026', NULL, 'Victoria Road terrace, near shops', 'Listing in Brighton, Victoria. Demo Victoria market for HouGarden-style site.', 2, 'sale', 'available', '2780000.00', 0, 'AUD', '2/88 Victoria Road', 'Bellevue Hill', 'Eastern Suburbs', 'NSW', '2023', 'Australia', '2.0', '1.0', 980, NULL, NULL, 'Price Reduced', 0, 0, 1, 0, 2, NULL, '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/search/buy/list_in_suburb-bellevue-hill-11110142_page-1', 'HG-AU-026', NULL, '2026-11-01 02:17:47', '2026-11-01 22:55:29'),
(27, '4-120-old-south-head-road-rose-bay-27', 'HG-AU-027', NULL, 'Water glimpses, Old South Head Road', 'Listing in East Melbourne, Victoria. Demo Victoria market for HouGarden-style site.', 5, 'sale', 'available', '3650000.00', 0, 'AUD', '4/120 Old South Head Road', 'Rose Bay', 'Eastern Suburbs', 'NSW', '2029', 'Australia', '3.0', '2.0', 1650, NULL, NULL, NULL, 1, 0, 1, 1, 3, NULL, '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/search/buy/list_in_suburb-rose-bay-11110143_page-1', 'HG-AU-027', NULL, '2026-11-01 02:17:47', '2026-11-01 22:55:33'),
(28, '1-45-dover-road-rose-bay-28', 'HG-AU-028', NULL, 'Garden apartment, Dover Road', 'Listing in Melbourne, Victoria. Demo Victoria market for HouGarden-style site.', 2, 'sale', 'available', '1895000.00', 0, 'AUD', '1/45 Dover Road', 'Rose Bay', 'Eastern Suburbs', 'NSW', '2029', 'Australia', '2.0', '2.0', 1100, NULL, NULL, 'Just Listed', 0, 0, 0, 1, 4, NULL, '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/search/buy/list_in_suburb-rose-bay-11110143_page-1', 'HG-AU-028', NULL, '2026-11-01 02:17:47', '2026-11-01 22:55:37'),
(29, '6-180-toorak-road-south-yarra-29', 'HG-AU-029', NULL, 'Toorak Road boutique apartment', NULL, 2, 'sale', 'available', '1420000.00', 0, 'AUD', '6/180 Toorak Road', 'South Yarra', 'Inner Melbourne', 'VIC', '3141', 'Australia', '2.0', '2.0', 1050, NULL, NULL, NULL, 0, 0, 0, 1, 1, NULL, '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/search/buy/list_in_suburb-south-yarra-11110144_page-1', 'HG-AU-029', NULL, '2026-11-01 02:17:47', '2026-11-01 17:09:43'),
(30, '12-domain-road-south-yarra-30', 'HG-AU-030', NULL, 'Domain precinct, Domain Road', NULL, 5, 'sale', 'available', '5200000.00', 0, 'AUD', '12 Domain Road', 'South Yarra', 'Inner Melbourne', 'VIC', '3141', 'Australia', '4.0', '3.0', 3200, NULL, NULL, 'Exclusive', 1, 0, 0, 1, 2, NULL, '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/search/buy/list_in_suburb-south-yarra-11110144_page-1', 'HG-AU-030', NULL, '2026-11-01 02:17:47', '2026-11-01 17:09:43'),
(31, '22-the-esplanade-ocean-grove-31', 'HG-AU-031', NULL, 'Coastal family retreat, The Esplanade', NULL, 5, 'sale', 'available', '2450000.00', 0, 'AUD', '22 The Esplanade', 'Ocean Grove', 'Bellarine Peninsula', 'VIC', '3226', 'Australia', '4.0', '3.0', 2600, NULL, NULL, NULL, 0, 0, 0, 1, 3, NULL, '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/search/buy/list_in_suburb-ocean-grove-11110288_page-1', 'HG-AU-031', NULL, '2026-11-01 02:17:47', '2026-11-01 17:09:43'),
(32, '3-15-kingston-downs-drive-ocean-grove-32', 'HG-AU-032', NULL, 'Townhouse, Kingston Downs Drive', NULL, 5, 'sale', 'available', '895000.00', 0, 'AUD', '3/15 Kingston Downs Drive', 'Ocean Grove', 'Bellarine Peninsula', 'VIC', '3226', 'Australia', '3.0', '2.0', 1450, NULL, NULL, 'Open Home', 0, 0, 0, 1, 4, NULL, '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/search/buy/list_in_suburb-ocean-grove-11110288_page-1', 'HG-AU-032', NULL, '2026-11-01 02:17:47', '2026-11-01 17:09:43'),
(33, '18-grubb-road-ocean-grove-33', 'HG-AU-033', NULL, 'Corner block, Grubb Road', NULL, 5, 'sale', 'available', '680000.00', 0, 'AUD', '18 Grubb Road', 'Ocean Grove', 'Bellarine Peninsula', 'VIC', '3226', 'Australia', NULL, NULL, NULL, NULL, NULL, 'Land', 0, 0, 0, 1, 1, NULL, '2026-11-01', 'hougarden_au', 'https://www.hougarden.com/au/search/buy/list_in_suburb-ocean-grove-11110288_page-1', 'HG-AU-033', NULL, '2026-11-01 02:17:47', '2026-11-01 17:09:43');

DELETE FROM `property_images`;
INSERT INTO `property_images` (`id`, `property_id`, `path`, `alt_text`, `caption`, `sort_order`, `is_cover`, `created_at`, `updated_at`) VALUES
(380, 1, 'uploads/properties/hg-1/cover.jpg', 'Whole-floor garden residence, Bellevue Road — 1', NULL, 0, 1, '2026-11-01 22:54:25', '2026-11-01 22:54:25'),
(381, 3, 'uploads/properties/hg-3/cover.jpg', 'Harbour-side apartment, Victoria Road — 1', NULL, 0, 1, '2026-11-01 22:54:43', '2026-11-01 22:54:43'),
(382, 4, 'uploads/properties/hg-4/cover.jpg', 'Cooper Park outlook, Bellevue Road — 1', NULL, 0, 1, '2026-11-01 22:54:45', '2026-11-01 22:54:45'),
(383, 5, 'uploads/properties/hg-5/cover.jpg', 'Village proximity, Crana Avenue — 1', NULL, 0, 1, '2026-11-01 22:54:47', '2026-11-01 22:54:47'),
(384, 7, 'uploads/properties/hg-7/cover.jpg', 'Whole-floor garden apartment, Spencer Street — 1', NULL, 0, 1, '2026-11-01 22:54:50', '2026-11-01 22:54:50'),
(385, 8, 'uploads/properties/hg-8/cover.jpg', 'Strana whole-floor apartment, Albemarle Avenue — 1', NULL, 0, 1, '2026-11-01 22:54:52', '2026-11-01 22:54:52'),
(386, 9, 'uploads/properties/hg-9/cover.jpg', 'Harbourside apartment, Old South Head Road — 1', NULL, 0, 1, '2026-11-01 22:54:54', '2026-11-01 22:54:54'),
(387, 10, 'uploads/properties/hg-10/cover.jpg', 'Renovated art-deco, Balfour Road — 1', NULL, 0, 1, '2026-11-01 22:54:59', '2026-11-01 22:54:59'),
(388, 11, 'uploads/properties/hg-11/cover.jpg', 'Garden apartment, Plumer Road — 1', NULL, 0, 1, '2026-11-01 22:55:02', '2026-11-01 22:55:02'),
(389, 12, 'uploads/properties/hg-12/cover.jpg', 'North-facing garden apartment, Old South Head Road — 1', NULL, 0, 1, '2026-11-01 22:55:04', '2026-11-01 22:55:04'),
(390, 15, 'uploads/properties/hg-15/cover.jpg', 'Yarra-side residence, Domain Road — 1', NULL, 0, 1, '2026-11-01 22:55:11', '2026-11-01 22:55:11'),
(391, 18, 'uploads/properties/hg-18/cover.jpg', 'Modern terrace, Fawkner Street — 1', NULL, 0, 1, '2026-11-01 22:55:15', '2026-11-01 22:55:15'),
(392, 19, 'uploads/properties/hg-19/cover.jpg', 'Coastal family home, Sunset Strip — 1', NULL, 0, 1, '2026-11-01 22:55:17', '2026-11-01 22:55:17'),
(393, 20, 'uploads/properties/hg-20/cover.jpg', 'Beachside living, Sunbeam Street — 1', NULL, 0, 1, '2026-11-01 22:55:19', '2026-11-01 22:55:19'),
(394, 21, 'uploads/properties/hg-21/cover.jpg', 'Elevated coastal home, Bonnyvale Road — 1', NULL, 0, 1, '2026-11-01 22:55:21', '2026-11-01 22:55:21'),
(395, 22, 'uploads/properties/hg-22/cover.jpg', 'Family home, Parkside Boulevard — 1', NULL, 0, 1, '2026-11-01 22:55:22', '2026-11-01 22:55:22'),
(396, 23, 'uploads/properties/hg-23/cover.jpg', 'Near the beach, Shorebreak Way — 1', NULL, 0, 1, '2026-11-01 22:55:25', '2026-11-01 22:55:25'),
(397, 24, 'uploads/properties/hg-24/cover.jpg', 'Aldebaran Road family home — 1', NULL, 0, 1, '2026-11-01 22:55:27', '2026-11-01 22:55:27'),
(398, 25, 'uploads/properties/hg-25/cover.jpg', 'Penthouse with district views, Bellevue Road — 1', NULL, 0, 1, '2026-11-01 22:55:29', '2026-11-01 22:55:29'),
(399, 26, 'uploads/properties/hg-26/cover.jpg', 'Victoria Road terrace, near shops — 1', NULL, 0, 1, '2026-11-01 22:55:33', '2026-11-01 22:55:33'),
(400, 27, 'uploads/properties/hg-27/cover.jpg', 'Water glimpses, Old South Head Road — 1', NULL, 0, 1, '2026-11-01 22:55:37', '2026-11-01 22:55:37'),
(401, 29, 'uploads/properties/hg-29/cover.jpg', 'Toorak Road boutique apartment — 1', NULL, 0, 1, '2026-11-01 22:55:41', '2026-11-01 22:55:41'),
(402, 30, 'uploads/properties/hg-30/cover.jpg', 'Domain precinct, Domain Road — 1', NULL, 0, 1, '2026-11-01 22:55:43', '2026-11-01 22:55:43'),
(403, 31, 'uploads/properties/hg-31/cover.jpg', 'Coastal family retreat, The Esplanade — 1', NULL, 0, 1, '2026-11-01 22:55:45', '2026-11-01 22:55:45'),
(404, 33, 'uploads/properties/hg-33/cover.jpg', 'Corner block, Grubb Road — 1', NULL, 0, 1, '2026-11-01 22:55:48', '2026-11-01 22:55:48'),
(405, 1, 'uploads/properties/hg-1/gallery-2.jpg', 'Whole-floor garden residence, Bellevue Road — photo 2', NULL, 1, 0, '2026-11-01 22:55:53', '2026-11-01 22:55:53'),
(406, 1, 'uploads/properties/hg-1/gallery-3.jpg', 'Whole-floor garden residence, Bellevue Road — photo 3', NULL, 2, 0, '2026-11-01 22:55:53', '2026-11-01 22:55:53'),
(407, 1, 'uploads/properties/hg-1/gallery-5.jpg', 'Whole-floor garden residence, Bellevue Road — photo 5', NULL, 4, 0, '2026-11-01 22:55:54', '2026-11-01 22:55:54'),
(408, 1, 'uploads/properties/hg-1/gallery-6.jpg', 'Whole-floor garden residence, Bellevue Road — photo 6', NULL, 5, 0, '2026-11-01 22:55:54', '2026-11-01 22:55:54'),
(409, 1, 'uploads/properties/hg-1/gallery-7.jpg', 'Whole-floor garden residence, Bellevue Road — photo 7', NULL, 6, 0, '2026-11-01 22:55:54', '2026-11-01 22:55:54'),
(410, 1, 'uploads/properties/hg-1/gallery-8.jpg', 'Whole-floor garden residence, Bellevue Road — photo 8', NULL, 7, 0, '2026-11-01 22:55:54', '2026-11-01 22:55:54'),
(411, 2, 'uploads/properties/hg-2/gallery-1.jpg', 'Otium garden residence, Victoria Road — photo 1', NULL, 0, 1, '2026-11-01 22:55:55', '2026-11-01 22:55:55'),
(412, 2, 'uploads/properties/hg-2/gallery-2.jpg', 'Otium garden residence, Victoria Road — photo 2', NULL, 1, 0, '2026-11-01 22:55:55', '2026-11-01 22:55:55'),
(413, 2, 'uploads/properties/hg-2/gallery-3.jpg', 'Otium garden residence, Victoria Road — photo 3', NULL, 2, 0, '2026-11-01 22:55:55', '2026-11-01 22:55:55'),
(414, 2, 'uploads/properties/hg-2/gallery-4.jpg', 'Otium garden residence, Victoria Road — photo 4', NULL, 3, 0, '2026-11-01 22:55:55', '2026-11-01 22:55:55'),
(415, 2, 'uploads/properties/hg-2/gallery-5.jpg', 'Otium garden residence, Victoria Road — photo 5', NULL, 4, 0, '2026-11-01 22:55:55', '2026-11-01 22:55:55'),
(416, 2, 'uploads/properties/hg-2/gallery-6.jpg', 'Otium garden residence, Victoria Road — photo 6', NULL, 5, 0, '2026-11-01 22:55:55', '2026-11-01 22:55:55'),
(417, 2, 'uploads/properties/hg-2/gallery-7.jpg', 'Otium garden residence, Victoria Road — photo 7', NULL, 6, 0, '2026-11-01 22:55:55', '2026-11-01 22:55:55'),
(418, 2, 'uploads/properties/hg-2/gallery-8.jpg', 'Otium garden residence, Victoria Road — photo 8', NULL, 7, 0, '2026-11-01 22:55:55', '2026-11-01 22:55:55'),
(419, 3, 'uploads/properties/hg-3/gallery-2.jpg', 'Harbour-side apartment, Victoria Road — photo 2', NULL, 1, 0, '2026-11-01 22:55:55', '2026-11-01 22:55:55'),
(420, 3, 'uploads/properties/hg-3/gallery-4.jpg', 'Harbour-side apartment, Victoria Road — photo 4', NULL, 3, 0, '2026-11-01 22:55:57', '2026-11-01 22:55:57'),
(421, 3, 'uploads/properties/hg-3/gallery-5.jpg', 'Harbour-side apartment, Victoria Road — photo 5', NULL, 4, 0, '2026-11-01 22:55:57', '2026-11-01 22:55:57'),
(422, 3, 'uploads/properties/hg-3/gallery-6.jpg', 'Harbour-side apartment, Victoria Road — photo 6', NULL, 5, 0, '2026-11-01 22:55:57', '2026-11-01 22:55:57'),
(423, 3, 'uploads/properties/hg-3/gallery-7.jpg', 'Harbour-side apartment, Victoria Road — photo 7', NULL, 6, 0, '2026-11-01 22:55:57', '2026-11-01 22:55:57'),
(424, 4, 'uploads/properties/hg-4/gallery-3.jpg', 'Cooper Park outlook, Bellevue Road — photo 3', NULL, 2, 0, '2026-11-01 22:55:59', '2026-11-01 22:55:59'),
(425, 4, 'uploads/properties/hg-4/gallery-4.jpg', 'Cooper Park outlook, Bellevue Road — photo 4', NULL, 3, 0, '2026-11-01 22:55:59', '2026-11-01 22:55:59'),
(426, 4, 'uploads/properties/hg-4/gallery-5.jpg', 'Cooper Park outlook, Bellevue Road — photo 5', NULL, 4, 0, '2026-11-01 22:55:59', '2026-11-01 22:55:59'),
(427, 4, 'uploads/properties/hg-4/gallery-6.jpg', 'Cooper Park outlook, Bellevue Road — photo 6', NULL, 5, 0, '2026-11-01 22:55:59', '2026-11-01 22:55:59'),
(428, 4, 'uploads/properties/hg-4/gallery-8.jpg', 'Cooper Park outlook, Bellevue Road — photo 8', NULL, 7, 0, '2026-11-01 22:56:00', '2026-11-01 22:56:00'),
(429, 5, 'uploads/properties/hg-5/gallery-2.jpg', 'Village proximity, Crana Avenue — photo 2', NULL, 1, 0, '2026-11-01 22:56:00', '2026-11-01 22:56:00'),
(430, 5, 'uploads/properties/hg-5/gallery-3.jpg', 'Village proximity, Crana Avenue — photo 3', NULL, 2, 0, '2026-11-01 22:56:00', '2026-11-01 22:56:00'),
(431, 5, 'uploads/properties/hg-5/gallery-4.jpg', 'Village proximity, Crana Avenue — photo 4', NULL, 3, 0, '2026-11-01 22:56:00', '2026-11-01 22:56:00'),
(432, 5, 'uploads/properties/hg-5/gallery-5.jpg', 'Village proximity, Crana Avenue — photo 5', NULL, 4, 0, '2026-11-01 22:56:00', '2026-11-01 22:56:00'),
(433, 5, 'uploads/properties/hg-5/gallery-6.jpg', 'Village proximity, Crana Avenue — photo 6', NULL, 5, 0, '2026-11-01 22:56:00', '2026-11-01 22:56:00'),
(434, 5, 'uploads/properties/hg-5/gallery-7.jpg', 'Village proximity, Crana Avenue — photo 7', NULL, 6, 0, '2026-11-01 22:56:00', '2026-11-01 22:56:00'),
(435, 5, 'uploads/properties/hg-5/gallery-8.jpg', 'Village proximity, Crana Avenue — photo 8', NULL, 7, 0, '2026-11-01 22:56:00', '2026-11-01 22:56:00'),
(436, 6, 'uploads/properties/hg-6/gallery-2.jpg', 'Designer terrace, Bellevue Road — photo 2', NULL, 1, 1, '2026-11-01 22:56:01', '2026-11-01 22:57:22'),
(437, 6, 'uploads/properties/hg-6/gallery-3.jpg', 'Designer terrace, Bellevue Road — photo 3', NULL, 2, 0, '2026-11-01 22:56:01', '2026-11-01 22:56:01'),
(438, 6, 'uploads/properties/hg-6/gallery-4.jpg', 'Designer terrace, Bellevue Road — photo 4', NULL, 3, 0, '2026-11-01 22:56:01', '2026-11-01 22:56:01'),
(439, 6, 'uploads/properties/hg-6/gallery-5.jpg', 'Designer terrace, Bellevue Road — photo 5', NULL, 4, 0, '2026-11-01 22:56:01', '2026-11-01 22:56:01'),
(440, 6, 'uploads/properties/hg-6/gallery-7.jpg', 'Designer terrace, Bellevue Road — photo 7', NULL, 6, 0, '2026-11-01 22:56:01', '2026-11-01 22:56:01'),
(441, 6, 'uploads/properties/hg-6/gallery-8.jpg', 'Designer terrace, Bellevue Road — photo 8', NULL, 7, 0, '2026-11-01 22:56:01', '2026-11-01 22:56:01'),
(442, 7, 'uploads/properties/hg-7/gallery-2.jpg', 'Whole-floor garden apartment, Spencer Street — photo 2', NULL, 1, 0, '2026-11-01 22:56:01', '2026-11-01 22:56:01'),
(443, 7, 'uploads/properties/hg-7/gallery-3.jpg', 'Whole-floor garden apartment, Spencer Street — photo 3', NULL, 2, 0, '2026-11-01 22:56:01', '2026-11-01 22:56:01'),
(444, 7, 'uploads/properties/hg-7/gallery-4.jpg', 'Whole-floor garden apartment, Spencer Street — photo 4', NULL, 3, 0, '2026-11-01 22:56:01', '2026-11-01 22:56:01'),
(445, 7, 'uploads/properties/hg-7/gallery-6.jpg', 'Whole-floor garden apartment, Spencer Street — photo 6', NULL, 5, 0, '2026-11-01 22:56:02', '2026-11-01 22:56:02'),
(446, 7, 'uploads/properties/hg-7/gallery-7.jpg', 'Whole-floor garden apartment, Spencer Street — photo 7', NULL, 6, 0, '2026-11-01 22:56:02', '2026-11-01 22:56:02'),
(447, 7, 'uploads/properties/hg-7/gallery-8.jpg', 'Whole-floor garden apartment, Spencer Street — photo 8', NULL, 7, 0, '2026-11-01 22:56:02', '2026-11-01 22:56:02'),
(448, 8, 'uploads/properties/hg-8/gallery-2.jpg', 'Strana whole-floor apartment, Albemarle Avenue — photo 2', NULL, 1, 0, '2026-11-01 22:56:02', '2026-11-01 22:56:02'),
(449, 8, 'uploads/properties/hg-8/gallery-3.jpg', 'Strana whole-floor apartment, Albemarle Avenue — photo 3', NULL, 2, 0, '2026-11-01 22:56:02', '2026-11-01 22:56:02'),
(450, 8, 'uploads/properties/hg-8/gallery-4.jpg', 'Strana whole-floor apartment, Albemarle Avenue — photo 4', NULL, 3, 0, '2026-11-01 22:56:02', '2026-11-01 22:56:02'),
(451, 8, 'uploads/properties/hg-8/gallery-5.jpg', 'Strana whole-floor apartment, Albemarle Avenue — photo 5', NULL, 4, 0, '2026-11-01 22:56:02', '2026-11-01 22:56:02'),
(452, 8, 'uploads/properties/hg-8/gallery-6.jpg', 'Strana whole-floor apartment, Albemarle Avenue — photo 6', NULL, 5, 0, '2026-11-01 22:56:02', '2026-11-01 22:56:02'),
(453, 8, 'uploads/properties/hg-8/gallery-7.jpg', 'Strana whole-floor apartment, Albemarle Avenue — photo 7', NULL, 6, 0, '2026-11-01 22:56:02', '2026-11-01 22:56:02'),
(454, 8, 'uploads/properties/hg-8/gallery-8.jpg', 'Strana whole-floor apartment, Albemarle Avenue — photo 8', NULL, 7, 0, '2026-11-01 22:56:02', '2026-11-01 22:56:02'),
(455, 9, 'uploads/properties/hg-9/gallery-2.jpg', 'Harbourside apartment, Old South Head Road — photo 2', NULL, 1, 0, '2026-11-01 22:56:02', '2026-11-01 22:56:02'),
(456, 9, 'uploads/properties/hg-9/gallery-3.jpg', 'Harbourside apartment, Old South Head Road — photo 3', NULL, 2, 0, '2026-11-01 22:56:02', '2026-11-01 22:56:02'),
(457, 9, 'uploads/properties/hg-9/gallery-5.jpg', 'Harbourside apartment, Old South Head Road — photo 5', NULL, 4, 0, '2026-11-01 22:56:03', '2026-11-01 22:56:03'),
(458, 9, 'uploads/properties/hg-9/gallery-6.jpg', 'Harbourside apartment, Old South Head Road — photo 6', NULL, 5, 0, '2026-11-01 22:56:03', '2026-11-01 22:56:03'),
(459, 9, 'uploads/properties/hg-9/gallery-7.jpg', 'Harbourside apartment, Old South Head Road — photo 7', NULL, 6, 0, '2026-11-01 22:56:03', '2026-11-01 22:56:03'),
(460, 9, 'uploads/properties/hg-9/gallery-8.jpg', 'Harbourside apartment, Old South Head Road — photo 8', NULL, 7, 0, '2026-11-01 22:56:03', '2026-11-01 22:56:03'),
(461, 10, 'uploads/properties/hg-10/gallery-2.jpg', 'Renovated art-deco, Balfour Road — photo 2', NULL, 1, 0, '2026-11-01 22:56:03', '2026-11-01 22:56:03'),
(462, 10, 'uploads/properties/hg-10/gallery-4.jpg', 'Renovated art-deco, Balfour Road — photo 4', NULL, 3, 0, '2026-11-01 22:56:04', '2026-11-01 22:56:04'),
(463, 10, 'uploads/properties/hg-10/gallery-5.jpg', 'Renovated art-deco, Balfour Road — photo 5', NULL, 4, 0, '2026-11-01 22:56:04', '2026-11-01 22:56:04'),
(464, 10, 'uploads/properties/hg-10/gallery-6.jpg', 'Renovated art-deco, Balfour Road — photo 6', NULL, 5, 0, '2026-11-01 22:56:04', '2026-11-01 22:56:04'),
(465, 10, 'uploads/properties/hg-10/gallery-7.jpg', 'Renovated art-deco, Balfour Road — photo 7', NULL, 6, 0, '2026-11-01 22:56:04', '2026-11-01 22:56:04'),
(466, 11, 'uploads/properties/hg-11/gallery-2.jpg', 'Garden apartment, Plumer Road — photo 2', NULL, 1, 0, '2026-11-01 22:56:06', '2026-11-01 22:56:06'),
(467, 11, 'uploads/properties/hg-11/gallery-3.jpg', 'Garden apartment, Plumer Road — photo 3', NULL, 2, 0, '2026-11-01 22:56:06', '2026-11-01 22:56:06'),
(468, 11, 'uploads/properties/hg-11/gallery-4.jpg', 'Garden apartment, Plumer Road — photo 4', NULL, 3, 0, '2026-11-01 22:56:06', '2026-11-01 22:56:06'),
(469, 11, 'uploads/properties/hg-11/gallery-5.jpg', 'Garden apartment, Plumer Road — photo 5', NULL, 4, 0, '2026-11-01 22:56:06', '2026-11-01 22:56:06'),
(470, 11, 'uploads/properties/hg-11/gallery-6.jpg', 'Garden apartment, Plumer Road — photo 6', NULL, 5, 0, '2026-11-01 22:56:06', '2026-11-01 22:56:06'),
(471, 11, 'uploads/properties/hg-11/gallery-7.jpg', 'Garden apartment, Plumer Road — photo 7', NULL, 6, 0, '2026-11-01 22:56:06', '2026-11-01 22:56:06'),
(472, 11, 'uploads/properties/hg-11/gallery-8.jpg', 'Garden apartment, Plumer Road — photo 8', NULL, 7, 0, '2026-11-01 22:56:06', '2026-11-01 22:56:06'),
(473, 12, 'uploads/properties/hg-12/gallery-3.jpg', 'North-facing garden apartment, Old South Head Road — photo 3', NULL, 2, 0, '2026-11-01 22:56:07', '2026-11-01 22:56:07'),
(474, 12, 'uploads/properties/hg-12/gallery-4.jpg', 'North-facing garden apartment, Old South Head Road — photo 4', NULL, 3, 0, '2026-11-01 22:56:07', '2026-11-01 22:56:07'),
(475, 12, 'uploads/properties/hg-12/gallery-5.jpg', 'North-facing garden apartment, Old South Head Road — photo 5', NULL, 4, 0, '2026-11-01 22:56:07', '2026-11-01 22:56:07'),
(476, 12, 'uploads/properties/hg-12/gallery-6.jpg', 'North-facing garden apartment, Old South Head Road — photo 6', NULL, 5, 0, '2026-11-01 22:56:07', '2026-11-01 22:56:07'),
(477, 12, 'uploads/properties/hg-12/gallery-8.jpg', 'North-facing garden apartment, Old South Head Road — photo 8', NULL, 7, 0, '2026-11-01 22:56:08', '2026-11-01 22:56:08'),
(478, 13, 'uploads/properties/hg-13/gallery-2.jpg', 'Unit 411/35 Wilson Street — photo 2', NULL, 1, 1, '2026-11-01 22:56:12', '2026-11-01 22:57:22'),
(479, 13, 'uploads/properties/hg-13/gallery-3.jpg', 'Unit 411/35 Wilson Street — photo 3', NULL, 2, 0, '2026-11-01 22:56:14', '2026-11-01 22:56:14'),
(480, 13, 'uploads/properties/hg-13/gallery-4.jpg', 'Unit 411/35 Wilson Street — photo 4', NULL, 3, 0, '2026-11-01 22:56:17', '2026-11-01 22:56:17'),
(481, 13, 'uploads/properties/hg-13/gallery-5.jpg', 'Unit 411/35 Wilson Street — photo 5', NULL, 4, 0, '2026-11-01 22:56:17', '2026-11-01 22:56:17'),
(482, 13, 'uploads/properties/hg-13/gallery-7.jpg', 'Unit 411/35 Wilson Street — photo 7', NULL, 6, 0, '2026-11-01 22:56:18', '2026-11-01 22:56:18'),
(483, 13, 'uploads/properties/hg-13/gallery-8.jpg', 'Unit 411/35 Wilson Street — photo 8', NULL, 7, 0, '2026-11-01 22:56:18', '2026-11-01 22:56:18'),
(484, 14, 'uploads/properties/hg-14/gallery-1.jpg', 'Toorak Road precinct apartment — photo 1', NULL, 0, 1, '2026-11-01 22:56:22', '2026-11-01 22:56:22'),
(485, 14, 'uploads/properties/hg-14/gallery-2.jpg', 'Toorak Road precinct apartment — photo 2', NULL, 1, 0, '2026-11-01 22:56:26', '2026-11-01 22:56:26'),
(486, 14, 'uploads/properties/hg-14/gallery-3.jpg', 'Toorak Road precinct apartment — photo 3', NULL, 2, 0, '2026-11-01 22:56:28', '2026-11-01 22:56:28'),
(487, 14, 'uploads/properties/hg-14/gallery-4.jpg', 'Toorak Road precinct apartment — photo 4', NULL, 3, 0, '2026-11-01 22:56:32', '2026-11-01 22:56:32'),
(488, 14, 'uploads/properties/hg-14/gallery-5.jpg', 'Toorak Road precinct apartment — photo 5', NULL, 4, 0, '2026-11-01 22:56:32', '2026-11-01 22:56:32'),
(489, 14, 'uploads/properties/hg-14/gallery-6.jpg', 'Toorak Road precinct apartment — photo 6', NULL, 5, 0, '2026-11-01 22:56:32', '2026-11-01 22:56:32'),
(490, 14, 'uploads/properties/hg-14/gallery-7.jpg', 'Toorak Road precinct apartment — photo 7', NULL, 6, 0, '2026-11-01 22:56:32', '2026-11-01 22:56:32'),
(491, 14, 'uploads/properties/hg-14/gallery-8.jpg', 'Toorak Road precinct apartment — photo 8', NULL, 7, 0, '2026-11-01 22:56:32', '2026-11-01 22:56:32'),
(492, 15, 'uploads/properties/hg-15/gallery-2.jpg', 'Yarra-side residence, Domain Road — photo 2', NULL, 1, 0, '2026-11-01 22:56:32', '2026-11-01 22:56:32'),
(493, 15, 'uploads/properties/hg-15/gallery-3.jpg', 'Yarra-side residence, Domain Road — photo 3', NULL, 2, 0, '2026-11-01 22:56:32', '2026-11-01 22:56:32'),
(494, 15, 'uploads/properties/hg-15/gallery-4.jpg', 'Yarra-side residence, Domain Road — photo 4', NULL, 3, 0, '2026-11-01 22:56:32', '2026-11-01 22:56:32'),
(495, 15, 'uploads/properties/hg-15/gallery-6.jpg', 'Yarra-side residence, Domain Road — photo 6', NULL, 5, 0, '2026-11-01 22:56:33', '2026-11-01 22:56:33'),
(496, 15, 'uploads/properties/hg-15/gallery-7.jpg', 'Yarra-side residence, Domain Road — photo 7', NULL, 6, 0, '2026-11-01 22:56:33', '2026-11-01 22:56:33'),
(497, 15, 'uploads/properties/hg-15/gallery-8.jpg', 'Yarra-side residence, Domain Road — photo 8', NULL, 7, 0, '2026-11-01 22:56:33', '2026-11-01 22:56:33'),
(498, 16, 'uploads/properties/hg-16/gallery-1.jpg', 'Chapel Street lifestyle, Arthur Street — photo 1', NULL, 0, 1, '2026-11-01 22:56:35', '2026-11-01 22:56:35'),
(499, 16, 'uploads/properties/hg-16/gallery-2.jpg', 'Chapel Street lifestyle, Arthur Street — photo 2', NULL, 1, 0, '2026-11-01 22:56:38', '2026-11-01 22:56:38'),
(500, 16, 'uploads/properties/hg-16/gallery-3.jpg', 'Chapel Street lifestyle, Arthur Street — photo 3', NULL, 2, 0, '2026-11-01 22:56:42', '2026-11-01 22:56:42'),
(501, 16, 'uploads/properties/hg-16/gallery-5.jpg', 'Chapel Street lifestyle, Arthur Street — photo 5', NULL, 4, 0, '2026-11-01 22:56:44', '2026-11-01 22:56:44'),
(502, 16, 'uploads/properties/hg-16/gallery-6.jpg', 'Chapel Street lifestyle, Arthur Street — photo 6', NULL, 5, 0, '2026-11-01 22:56:44', '2026-11-01 22:56:44'),
(503, 16, 'uploads/properties/hg-16/gallery-7.jpg', 'Chapel Street lifestyle, Arthur Street — photo 7', NULL, 6, 0, '2026-11-01 22:56:44', '2026-11-01 22:56:44'),
(504, 16, 'uploads/properties/hg-16/gallery-8.jpg', 'Chapel Street lifestyle, Arthur Street — photo 8', NULL, 7, 0, '2026-11-01 22:56:44', '2026-11-01 22:56:44'),
(505, 17, 'uploads/properties/hg-17/gallery-1.jpg', 'Botanic Gardens outlook, Anderson Street — photo 1', NULL, 0, 1, '2026-11-01 22:56:45', '2026-11-01 22:56:45'),
(506, 17, 'uploads/properties/hg-17/gallery-2.jpg', 'Botanic Gardens outlook, Anderson Street — photo 2', NULL, 1, 0, '2026-11-01 22:56:48', '2026-11-01 22:56:48'),
(507, 17, 'uploads/properties/hg-17/gallery-3.jpg', 'Botanic Gardens outlook, Anderson Street — photo 3', NULL, 2, 0, '2026-11-01 22:56:51', '2026-11-01 22:56:51'),
(508, 17, 'uploads/properties/hg-17/gallery-4.jpg', 'Botanic Gardens outlook, Anderson Street — photo 4', NULL, 3, 0, '2026-11-01 22:56:53', '2026-11-01 22:56:53'),
(509, 17, 'uploads/properties/hg-17/gallery-5.jpg', 'Botanic Gardens outlook, Anderson Street — photo 5', NULL, 4, 0, '2026-11-01 22:56:53', '2026-11-01 22:56:53'),
(510, 17, 'uploads/properties/hg-17/gallery-6.jpg', 'Botanic Gardens outlook, Anderson Street — photo 6', NULL, 5, 0, '2026-11-01 22:56:53', '2026-11-01 22:56:53'),
(511, 17, 'uploads/properties/hg-17/gallery-7.jpg', 'Botanic Gardens outlook, Anderson Street — photo 7', NULL, 6, 0, '2026-11-01 22:56:53', '2026-11-01 22:56:53'),
(512, 17, 'uploads/properties/hg-17/gallery-8.jpg', 'Botanic Gardens outlook, Anderson Street — photo 8', NULL, 7, 0, '2026-11-01 22:56:53', '2026-11-01 22:56:53'),
(513, 18, 'uploads/properties/hg-18/gallery-2.jpg', 'Modern terrace, Fawkner Street — photo 2', NULL, 1, 0, '2026-11-01 22:56:53', '2026-11-01 22:56:53'),
(514, 18, 'uploads/properties/hg-18/gallery-4.jpg', 'Modern terrace, Fawkner Street — photo 4', NULL, 3, 0, '2026-11-01 22:56:54', '2026-11-01 22:56:54'),
(515, 18, 'uploads/properties/hg-18/gallery-5.jpg', 'Modern terrace, Fawkner Street — photo 5', NULL, 4, 0, '2026-11-01 22:56:54', '2026-11-01 22:56:54'),
(516, 18, 'uploads/properties/hg-18/gallery-6.jpg', 'Modern terrace, Fawkner Street — photo 6', NULL, 5, 0, '2026-11-01 22:56:54', '2026-11-01 22:56:54'),
(517, 18, 'uploads/properties/hg-18/gallery-7.jpg', 'Modern terrace, Fawkner Street — photo 7', NULL, 6, 0, '2026-11-01 22:56:54', '2026-11-01 22:56:54'),
(518, 19, 'uploads/properties/hg-19/gallery-3.jpg', 'Coastal family home, Sunset Strip — photo 3', NULL, 2, 0, '2026-11-01 22:56:56', '2026-11-01 22:56:56'),
(519, 19, 'uploads/properties/hg-19/gallery-4.jpg', 'Coastal family home, Sunset Strip — photo 4', NULL, 3, 0, '2026-11-01 22:56:56', '2026-11-01 22:56:56'),
(520, 19, 'uploads/properties/hg-19/gallery-5.jpg', 'Coastal family home, Sunset Strip — photo 5', NULL, 4, 0, '2026-11-01 22:56:56', '2026-11-01 22:56:56'),
(521, 19, 'uploads/properties/hg-19/gallery-6.jpg', 'Coastal family home, Sunset Strip — photo 6', NULL, 5, 0, '2026-11-01 22:56:56', '2026-11-01 22:56:56'),
(522, 19, 'uploads/properties/hg-19/gallery-8.jpg', 'Coastal family home, Sunset Strip — photo 8', NULL, 7, 0, '2026-11-01 22:56:58', '2026-11-01 22:56:58'),
(523, 20, 'uploads/properties/hg-20/gallery-2.jpg', 'Beachside living, Sunbeam Street — photo 2', NULL, 1, 0, '2026-11-01 22:56:58', '2026-11-01 22:56:58'),
(524, 20, 'uploads/properties/hg-20/gallery-3.jpg', 'Beachside living, Sunbeam Street — photo 3', NULL, 2, 0, '2026-11-01 22:56:58', '2026-11-01 22:56:58'),
(525, 20, 'uploads/properties/hg-20/gallery-4.jpg', 'Beachside living, Sunbeam Street — photo 4', NULL, 3, 0, '2026-11-01 22:56:58', '2026-11-01 22:56:58'),
(526, 20, 'uploads/properties/hg-20/gallery-5.jpg', 'Beachside living, Sunbeam Street — photo 5', NULL, 4, 0, '2026-11-01 22:56:58', '2026-11-01 22:56:58'),
(527, 20, 'uploads/properties/hg-20/gallery-6.jpg', 'Beachside living, Sunbeam Street — photo 6', NULL, 5, 0, '2026-11-01 22:56:58', '2026-11-01 22:56:58'),
(528, 20, 'uploads/properties/hg-20/gallery-7.jpg', 'Beachside living, Sunbeam Street — photo 7', NULL, 6, 0, '2026-11-01 22:56:58', '2026-11-01 22:56:58'),
(529, 20, 'uploads/properties/hg-20/gallery-8.jpg', 'Beachside living, Sunbeam Street — photo 8', NULL, 7, 0, '2026-11-01 22:56:58', '2026-11-01 22:56:58'),
(530, 21, 'uploads/properties/hg-21/gallery-2.jpg', 'Elevated coastal home, Bonnyvale Road — photo 2', NULL, 1, 0, '2026-11-01 22:56:58', '2026-11-01 22:56:58'),
(531, 21, 'uploads/properties/hg-21/gallery-3.jpg', 'Elevated coastal home, Bonnyvale Road — photo 3', NULL, 2, 0, '2026-11-01 22:56:58', '2026-11-01 22:56:58'),
(532, 21, 'uploads/properties/hg-21/gallery-4.jpg', 'Elevated coastal home, Bonnyvale Road — photo 4', NULL, 3, 0, '2026-11-01 22:56:58', '2026-11-01 22:56:58'),
(533, 21, 'uploads/properties/hg-21/gallery-5.jpg', 'Elevated coastal home, Bonnyvale Road — photo 5', NULL, 4, 0, '2026-11-01 22:56:58', '2026-11-01 22:56:58'),
(534, 21, 'uploads/properties/hg-21/gallery-7.jpg', 'Elevated coastal home, Bonnyvale Road — photo 7', NULL, 6, 0, '2026-11-01 22:56:58', '2026-11-01 22:56:58'),
(535, 21, 'uploads/properties/hg-21/gallery-8.jpg', 'Elevated coastal home, Bonnyvale Road — photo 8', NULL, 7, 0, '2026-11-01 22:56:58', '2026-11-01 22:56:58'),
(536, 22, 'uploads/properties/hg-22/gallery-2.jpg', 'Family home, Parkside Boulevard — photo 2', NULL, 1, 0, '2026-11-01 22:56:58', '2026-11-01 22:56:58'),
(537, 22, 'uploads/properties/hg-22/gallery-3.jpg', 'Family home, Parkside Boulevard — photo 3', NULL, 2, 0, '2026-11-01 22:56:59', '2026-11-01 22:56:59'),
(538, 22, 'uploads/properties/hg-22/gallery-4.jpg', 'Family home, Parkside Boulevard — photo 4', NULL, 3, 0, '2026-11-01 22:56:59', '2026-11-01 22:56:59'),
(539, 22, 'uploads/properties/hg-22/gallery-6.jpg', 'Family home, Parkside Boulevard — photo 6', NULL, 5, 0, '2026-11-01 22:56:59', '2026-11-01 22:56:59'),
(540, 22, 'uploads/properties/hg-22/gallery-7.jpg', 'Family home, Parkside Boulevard — photo 7', NULL, 6, 0, '2026-11-01 22:56:59', '2026-11-01 22:56:59'),
(541, 22, 'uploads/properties/hg-22/gallery-8.jpg', 'Family home, Parkside Boulevard — photo 8', NULL, 7, 0, '2026-11-01 22:57:00', '2026-11-01 22:57:00'),
(542, 23, 'uploads/properties/hg-23/gallery-2.jpg', 'Near the beach, Shorebreak Way — photo 2', NULL, 1, 0, '2026-11-01 22:57:00', '2026-11-01 22:57:00'),
(543, 23, 'uploads/properties/hg-23/gallery-3.jpg', 'Near the beach, Shorebreak Way — photo 3', NULL, 2, 0, '2026-11-01 22:57:00', '2026-11-01 22:57:00'),
(544, 23, 'uploads/properties/hg-23/gallery-4.jpg', 'Near the beach, Shorebreak Way — photo 4', NULL, 3, 0, '2026-11-01 22:57:00', '2026-11-01 22:57:00'),
(545, 23, 'uploads/properties/hg-23/gallery-5.jpg', 'Near the beach, Shorebreak Way — photo 5', NULL, 4, 0, '2026-11-01 22:57:00', '2026-11-01 22:57:00'),
(546, 23, 'uploads/properties/hg-23/gallery-6.jpg', 'Near the beach, Shorebreak Way — photo 6', NULL, 5, 0, '2026-11-01 22:57:00', '2026-11-01 22:57:00'),
(547, 23, 'uploads/properties/hg-23/gallery-7.jpg', 'Near the beach, Shorebreak Way — photo 7', NULL, 6, 0, '2026-11-01 22:57:00', '2026-11-01 22:57:00'),
(548, 23, 'uploads/properties/hg-23/gallery-8.jpg', 'Near the beach, Shorebreak Way — photo 8', NULL, 7, 0, '2026-11-01 22:57:00', '2026-11-01 22:57:00'),
(549, 24, 'uploads/properties/hg-24/gallery-2.jpg', 'Aldebaran Road family home — photo 2', NULL, 1, 0, '2026-11-01 22:57:00', '2026-11-01 22:57:00'),
(550, 24, 'uploads/properties/hg-24/gallery-3.jpg', 'Aldebaran Road family home — photo 3', NULL, 2, 0, '2026-11-01 22:57:00', '2026-11-01 22:57:00'),
(551, 24, 'uploads/properties/hg-24/gallery-5.jpg', 'Aldebaran Road family home — photo 5', NULL, 4, 0, '2026-11-01 22:57:00', '2026-11-01 22:57:00'),
(552, 24, 'uploads/properties/hg-24/gallery-6.jpg', 'Aldebaran Road family home — photo 6', NULL, 5, 0, '2026-11-01 22:57:00', '2026-11-01 22:57:00'),
(553, 24, 'uploads/properties/hg-24/gallery-7.jpg', 'Aldebaran Road family home — photo 7', NULL, 6, 0, '2026-11-01 22:57:00', '2026-11-01 22:57:00'),
(554, 24, 'uploads/properties/hg-24/gallery-8.jpg', 'Aldebaran Road family home — photo 8', NULL, 7, 0, '2026-11-01 22:57:00', '2026-11-01 22:57:00'),
(555, 25, 'uploads/properties/hg-25/gallery-2.jpg', 'Penthouse with district views, Bellevue Road — photo 2', NULL, 1, 0, '2026-11-01 22:57:00', '2026-11-01 22:57:00'),
(556, 25, 'uploads/properties/hg-25/gallery-4.jpg', 'Penthouse with district views, Bellevue Road — photo 4', NULL, 3, 0, '2026-11-01 22:57:01', '2026-11-01 22:57:01'),
(557, 25, 'uploads/properties/hg-25/gallery-5.jpg', 'Penthouse with district views, Bellevue Road — photo 5', NULL, 4, 0, '2026-11-01 22:57:01', '2026-11-01 22:57:01'),
(558, 25, 'uploads/properties/hg-25/gallery-6.jpg', 'Penthouse with district views, Bellevue Road — photo 6', NULL, 5, 0, '2026-11-01 22:57:01', '2026-11-01 22:57:01'),
(559, 25, 'uploads/properties/hg-25/gallery-7.jpg', 'Penthouse with district views, Bellevue Road — photo 7', NULL, 6, 0, '2026-11-01 22:57:01', '2026-11-01 22:57:01'),
(560, 26, 'uploads/properties/hg-26/gallery-2.jpg', 'Victoria Road terrace, near shops — photo 2', NULL, 1, 0, '2026-11-01 22:57:02', '2026-11-01 22:57:02'),
(561, 26, 'uploads/properties/hg-26/gallery-3.jpg', 'Victoria Road terrace, near shops — photo 3', NULL, 2, 0, '2026-11-01 22:57:02', '2026-11-01 22:57:02'),
(562, 26, 'uploads/properties/hg-26/gallery-4.jpg', 'Victoria Road terrace, near shops — photo 4', NULL, 3, 0, '2026-11-01 22:57:02', '2026-11-01 22:57:02'),
(563, 26, 'uploads/properties/hg-26/gallery-5.jpg', 'Victoria Road terrace, near shops — photo 5', NULL, 4, 0, '2026-11-01 22:57:02', '2026-11-01 22:57:02'),
(564, 26, 'uploads/properties/hg-26/gallery-6.jpg', 'Victoria Road terrace, near shops — photo 6', NULL, 5, 0, '2026-11-01 22:57:02', '2026-11-01 22:57:02'),
(565, 26, 'uploads/properties/hg-26/gallery-7.jpg', 'Victoria Road terrace, near shops — photo 7', NULL, 6, 0, '2026-11-01 22:57:02', '2026-11-01 22:57:02'),
(566, 26, 'uploads/properties/hg-26/gallery-8.jpg', 'Victoria Road terrace, near shops — photo 8', NULL, 7, 0, '2026-11-01 22:57:02', '2026-11-01 22:57:02'),
(567, 27, 'uploads/properties/hg-27/gallery-3.jpg', 'Water glimpses, Old South Head Road — photo 3', NULL, 2, 0, '2026-11-01 22:57:04', '2026-11-01 22:57:04'),
(568, 27, 'uploads/properties/hg-27/gallery-4.jpg', 'Water glimpses, Old South Head Road — photo 4', NULL, 3, 0, '2026-11-01 22:57:04', '2026-11-01 22:57:04'),
(569, 27, 'uploads/properties/hg-27/gallery-5.jpg', 'Water glimpses, Old South Head Road — photo 5', NULL, 4, 0, '2026-11-01 22:57:04', '2026-11-01 22:57:04'),
(570, 27, 'uploads/properties/hg-27/gallery-6.jpg', 'Water glimpses, Old South Head Road — photo 6', NULL, 5, 0, '2026-11-01 22:57:04', '2026-11-01 22:57:04'),
(571, 27, 'uploads/properties/hg-27/gallery-8.jpg', 'Water glimpses, Old South Head Road — photo 8', NULL, 7, 0, '2026-11-01 22:57:05', '2026-11-01 22:57:05'),
(572, 28, 'uploads/properties/hg-28/gallery-2.jpg', 'Garden apartment, Dover Road — photo 2', NULL, 1, 1, '2026-11-01 22:57:08', '2026-11-01 22:57:22'),
(573, 28, 'uploads/properties/hg-28/gallery-3.jpg', 'Garden apartment, Dover Road — photo 3', NULL, 2, 0, '2026-11-01 22:57:09', '2026-11-01 22:57:09'),
(574, 28, 'uploads/properties/hg-28/gallery-4.jpg', 'Garden apartment, Dover Road — photo 4', NULL, 3, 0, '2026-11-01 22:57:11', '2026-11-01 22:57:11'),
(575, 28, 'uploads/properties/hg-28/gallery-5.jpg', 'Garden apartment, Dover Road — photo 5', NULL, 4, 0, '2026-11-01 22:57:11', '2026-11-01 22:57:11'),
(576, 28, 'uploads/properties/hg-28/gallery-7.jpg', 'Garden apartment, Dover Road — photo 7', NULL, 6, 0, '2026-11-01 22:57:11', '2026-11-01 22:57:11'),
(577, 28, 'uploads/properties/hg-28/gallery-8.jpg', 'Garden apartment, Dover Road — photo 8', NULL, 7, 0, '2026-11-01 22:57:11', '2026-11-01 22:57:11'),
(578, 29, 'uploads/properties/hg-29/gallery-2.jpg', 'Toorak Road boutique apartment — photo 2', NULL, 1, 0, '2026-11-01 22:57:11', '2026-11-01 22:57:11'),
(579, 29, 'uploads/properties/hg-29/gallery-3.jpg', 'Toorak Road boutique apartment — photo 3', NULL, 2, 0, '2026-11-01 22:57:11', '2026-11-01 22:57:11'),
(580, 29, 'uploads/properties/hg-29/gallery-4.jpg', 'Toorak Road boutique apartment — photo 4', NULL, 3, 0, '2026-11-01 22:57:11', '2026-11-01 22:57:11'),
(581, 29, 'uploads/properties/hg-29/gallery-5.jpg', 'Toorak Road boutique apartment — photo 5', NULL, 4, 0, '2026-11-01 22:57:11', '2026-11-01 22:57:11'),
(582, 29, 'uploads/properties/hg-29/gallery-6.jpg', 'Toorak Road boutique apartment — photo 6', NULL, 5, 0, '2026-11-01 22:57:11', '2026-11-01 22:57:11'),
(583, 29, 'uploads/properties/hg-29/gallery-7.jpg', 'Toorak Road boutique apartment — photo 7', NULL, 6, 0, '2026-11-01 22:57:11', '2026-11-01 22:57:11'),
(584, 29, 'uploads/properties/hg-29/gallery-8.jpg', 'Toorak Road boutique apartment — photo 8', NULL, 7, 0, '2026-11-01 22:57:11', '2026-11-01 22:57:11'),
(585, 30, 'uploads/properties/hg-30/gallery-2.jpg', 'Domain precinct, Domain Road — photo 2', NULL, 1, 0, '2026-11-01 22:57:11', '2026-11-01 22:57:11'),
(586, 30, 'uploads/properties/hg-30/gallery-3.jpg', 'Domain precinct, Domain Road — photo 3', NULL, 2, 0, '2026-11-01 22:57:11', '2026-11-01 22:57:11'),
(587, 30, 'uploads/properties/hg-30/gallery-4.jpg', 'Domain precinct, Domain Road — photo 4', NULL, 3, 0, '2026-11-01 22:57:11', '2026-11-01 22:57:11'),
(588, 30, 'uploads/properties/hg-30/gallery-6.jpg', 'Domain precinct, Domain Road — photo 6', NULL, 5, 0, '2026-11-01 22:57:13', '2026-11-01 22:57:13'),
(589, 30, 'uploads/properties/hg-30/gallery-7.jpg', 'Domain precinct, Domain Road — photo 7', NULL, 6, 0, '2026-11-01 22:57:13', '2026-11-01 22:57:13'),
(590, 30, 'uploads/properties/hg-30/gallery-8.jpg', 'Domain precinct, Domain Road — photo 8', NULL, 7, 0, '2026-11-01 22:57:13', '2026-11-01 22:57:13'),
(591, 31, 'uploads/properties/hg-31/gallery-2.jpg', 'Coastal family retreat, The Esplanade — photo 2', NULL, 1, 0, '2026-11-01 22:57:13', '2026-11-01 22:57:13'),
(592, 31, 'uploads/properties/hg-31/gallery-3.jpg', 'Coastal family retreat, The Esplanade — photo 3', NULL, 2, 0, '2026-11-01 22:57:13', '2026-11-01 22:57:13'),
(593, 31, 'uploads/properties/hg-31/gallery-5.jpg', 'Coastal family retreat, The Esplanade — photo 5', NULL, 4, 0, '2026-11-01 22:57:14', '2026-11-01 22:57:14'),
(594, 31, 'uploads/properties/hg-31/gallery-6.jpg', 'Coastal family retreat, The Esplanade — photo 6', NULL, 5, 0, '2026-11-01 22:57:14', '2026-11-01 22:57:14'),
(595, 31, 'uploads/properties/hg-31/gallery-7.jpg', 'Coastal family retreat, The Esplanade — photo 7', NULL, 6, 0, '2026-11-01 22:57:14', '2026-11-01 22:57:14'),
(596, 31, 'uploads/properties/hg-31/gallery-8.jpg', 'Coastal family retreat, The Esplanade — photo 8', NULL, 7, 0, '2026-11-01 22:57:14', '2026-11-01 22:57:14'),
(597, 32, 'uploads/properties/hg-32/gallery-1.jpg', 'Townhouse, Kingston Downs Drive — photo 1', NULL, 0, 1, '2026-11-01 22:57:15', '2026-11-01 22:57:15'),
(598, 32, 'uploads/properties/hg-32/gallery-2.jpg', 'Townhouse, Kingston Downs Drive — photo 2', NULL, 1, 0, '2026-11-01 22:57:16', '2026-11-01 22:57:16'),
(599, 32, 'uploads/properties/hg-32/gallery-3.jpg', 'Townhouse, Kingston Downs Drive — photo 3', NULL, 2, 0, '2026-11-01 22:57:19', '2026-11-01 22:57:19'),
(600, 32, 'uploads/properties/hg-32/gallery-4.jpg', 'Townhouse, Kingston Downs Drive — photo 4', NULL, 3, 0, '2026-11-01 22:57:20', '2026-11-01 22:57:20'),
(601, 32, 'uploads/properties/hg-32/gallery-5.jpg', 'Townhouse, Kingston Downs Drive — photo 5', NULL, 4, 0, '2026-11-01 22:57:20', '2026-11-01 22:57:20'),
(602, 32, 'uploads/properties/hg-32/gallery-6.jpg', 'Townhouse, Kingston Downs Drive — photo 6', NULL, 5, 0, '2026-11-01 22:57:20', '2026-11-01 22:57:20'),
(603, 32, 'uploads/properties/hg-32/gallery-7.jpg', 'Townhouse, Kingston Downs Drive — photo 7', NULL, 6, 0, '2026-11-01 22:57:20', '2026-11-01 22:57:20'),
(604, 32, 'uploads/properties/hg-32/gallery-8.jpg', 'Townhouse, Kingston Downs Drive — photo 8', NULL, 7, 0, '2026-11-01 22:57:20', '2026-11-01 22:57:20'),
(605, 33, 'uploads/properties/hg-33/gallery-2.jpg', 'Corner block, Grubb Road — photo 2', NULL, 1, 0, '2026-11-01 22:57:20', '2026-11-01 22:57:20'),
(606, 33, 'uploads/properties/hg-33/gallery-4.jpg', 'Corner block, Grubb Road — photo 4', NULL, 3, 0, '2026-11-01 22:57:22', '2026-11-01 22:57:22'),
(607, 33, 'uploads/properties/hg-33/gallery-5.jpg', 'Corner block, Grubb Road — photo 5', NULL, 4, 0, '2026-11-01 22:57:22', '2026-11-01 22:57:22'),
(608, 33, 'uploads/properties/hg-33/gallery-6.jpg', 'Corner block, Grubb Road — photo 6', NULL, 5, 0, '2026-11-01 22:57:22', '2026-11-01 22:57:22'),
(609, 33, 'uploads/properties/hg-33/gallery-7.jpg', 'Corner block, Grubb Road — photo 7', NULL, 6, 0, '2026-11-01 22:57:22', '2026-11-01 22:57:22');

DELETE FROM `property_amenity`;
INSERT INTO `property_amenity` (`property_id`, `amenity_id`) VALUES
(8, 1),
(8, 2),
(8, 5),
(8, 7);

SET FOREIGN_KEY_CHECKS = 1;
