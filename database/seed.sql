-- Safe seed data — NO admin passwords.
-- Import AFTER schema.sql into the SAME database (already selected in phpMyAdmin).
-- Admin account is created via one-time setup (admin/setup.php).
--
-- Uses DELETE (not TRUNCATE) so phpMyAdmin / MariaDB foreign keys do not block re-seed.

SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM `property_amenity`;
DELETE FROM `property_images`;
DELETE FROM `inquiries`;
DELETE FROM `properties`;
DELETE FROM `amenities`;
DELETE FROM `property_types`;
DELETE FROM `regions`;
DELETE FROM `agents`;
DELETE FROM `offices`;
DELETE FROM `settings`;
-- users intentionally left empty (no password seed)
SET FOREIGN_KEY_CHECKS = 1;

-- Property types (from reference add form + filters)
INSERT INTO `property_types` (`slug`, `name`, `sort_order`) VALUES
  ('estate', 'Estate', 10),
  ('condo', 'Luxury Condo', 20),
  ('ranch', 'Ranch', 30),
  ('chalet', 'Chalet', 40),
  ('single_family', 'Single Family', 50),
  ('land', 'Land', 60);

INSERT INTO `regions` (`slug`, `name`, `sort_order`, `is_active`, `is_featured`, `image_path`) VALUES
  ('aspen', 'Aspen', 10, 1, 1, 'assets/img/collection-aspen.jpg'),
  ('vail', 'Vail', 20, 1, 1, 'assets/img/collection-vail.jpg'),
  ('beaver-creek', 'Beaver Creek', 30, 1, 1, 'assets/img/collection-beaver-creek.jpg'),
  ('telluride', 'Telluride', 40, 1, 0, NULL),
  ('snowmass', 'Snowmass', 50, 1, 0, NULL),
  ('steamboat', 'Steamboat', 60, 1, 0, NULL),
  ('denver-metro', 'Denver Metro', 70, 1, 0, NULL);

-- Amenities (from reference add form)
INSERT INTO `amenities` (`slug`, `name`, `category`, `sort_order`) VALUES
  ('smart-home', 'Smart Home Tech', 'interior', 10),
  ('wine-cellar', 'Wine Cellar', 'interior', 20),
  ('home-theater', 'Home Theater', 'interior', 30),
  ('ski-in-ski-out', 'Ski-In/Ski-Out', 'exterior', 40),
  ('heated-driveway', 'Heated Driveway', 'exterior', 50),
  ('outdoor-kitchen', 'Outdoor Kitchen', 'exterior', 60),
  ('gated-access', 'Gated Access', 'community', 70),
  ('clubhouse', 'Clubhouse', 'community', 80);

-- Agents (from our_experts reference — unique rows)
INSERT INTO `agents` (`slug`, `name`, `title`, `region`, `bio`, `badge`, `photo_path`, `sort_order`, `is_active`) VALUES
  ('eleanor-vance', 'Eleanor Vance', 'Managing Broker', 'Aspen Core',
   'With over two decades navigating Aspen''s complex luxury market, Eleanor provides unmatched strategic advisory for ultra-high-net-worth clientele seeking legacy properties.',
   'Top Producer', 'assets/img/agent-eleanor-vance.jpg', 10, 1),
  ('julian-thorne', 'Julian Thorne', 'Global Luxury Specialist', 'Vail',
   'Specializing in ski-in/ski-out estates and private ranches. Julian''s international network connects global buyers with Colorado''s most exclusive alpine retreats.',
   NULL, 'assets/img/agent-julian-thorne.jpg', 20, 1),
  ('chloe-sterling', 'Chloe Sterling', 'Principal Agent', 'Denver Metro',
   'Chloe brings a data-driven approach to Denver''s high-end urban market, focusing on luxury penthouses and historic Cherry Creek estates with unparalleled precision.',
   NULL, 'assets/img/agent-chloe-sterling.jpg', 30, 1),
  ('marcus-wright', 'Marcus Wright', 'Ranch & Land Director', 'Telluride',
   'The definitive authority on large-scale Colorado land acquisitions. Marcus specializes in sporting properties, equestrian estates, and conservation easements.',
   NULL, 'assets/img/agent-marcus-wright.jpg', 40, 1);

-- Offices (from contact/footer reference)
INSERT INTO `offices` (`name`, `address_line`, `city`, `region`, `postal_code`, `sort_order`, `is_active`) VALUES
  ('Vail Village', '', 'Vail', 'Vail Valley', '', 10, 1),
  ('Beaver Creek', '', 'Beaver Creek', 'Vail Valley', '', 20, 1),
  ('Aspen Core', '', 'Aspen', 'Roaring Fork', '', 30, 1),
  ('Denver Cherry Creek', '', 'Denver', 'Denver Metro', '', 40, 1);

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
  ('site_phone', '800.555.0123'),
  ('site_email', 'info@example.com'),
  ('site_name', 'Sunview Development and Consultancy (SDC)'),
  ('site_logo_path', NULL),
  ('site_favicon_path', NULL),
  ('mail_from_name', NULL);

-- NOTE: Admin setup is detected ONLY from the users table (no settings flag).
-- Never place plaintext or known default passwords in this file.

-- HouGarden AU listings are NOT seeded here (no Colorado / parent-company demo inventory).
-- After schema + this seed file:
--   php scripts/apply-hougarden-manifest.php
--   php scripts/seed-property-galleries.php
--   php scripts/cleanup-hougarden-assets.php
-- Visit admin/setup.php once to create the first admin password.

-- Legacy property/image seed removed intentionally (see git history).

-- ---------------------------------------------------------------------------
-- ADMIN SETUP NOTE
-- ---------------------------------------------------------------------------
-- No rows are inserted into `users`.
-- Visit admin/setup.php once to create the first admin:
--   password_hash() / PASSWORD_DEFAULT; sets users.setup_completed_at.
-- Never place plaintext or known default passwords in this file.

