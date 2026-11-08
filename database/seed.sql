-- HouGarden US demo taxonomy seed (import after schema.sql or use hougarden-deploy.sql).
-- Homepage Locations = regions with is_featured = 1 (edit in Admin → Homepage locations).

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
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO `property_types` (`slug`, `name`, `sort_order`, `is_active`) VALUES
  ('single_family', 'House', 10, 1),
  ('condo', 'Apartment', 20, 1),
  ('townhouse', 'Townhouse', 30, 1),
  ('land', 'Land', 40, 1);

INSERT INTO `regions` (`slug`, `name`, `sort_order`, `is_active`, `is_featured`, `image_path`, `home_filter_key`, `home_filter_value`) VALUES
  ('austin-metro', 'Austin', 10, 1, 1, NULL, 'location', 'Austin'),
  ('phoenix-metro', 'Phoenix', 20, 1, 1, NULL, 'location', 'Phoenix'),
  ('atlanta-metro', 'Atlanta', 30, 1, 1, NULL, 'location', 'Atlanta'),
  ('denver-metro', 'Denver', 40, 1, 1, NULL, 'location', 'Denver'),
  ('chicago-metro', 'Chicago', 50, 1, 1, NULL, 'location', 'Chicago');

INSERT INTO `amenities` (`slug`, `name`, `category`, `sort_order`, `is_active`) VALUES
  ('pool', 'Swimming pool', 'exterior', 10, 1),
  ('garage', 'Garage / car space', 'exterior', 20, 1),
  ('air-conditioning', 'Air conditioning', 'interior', 30, 1),
  ('outdoor-entertaining', 'Outdoor entertaining', 'exterior', 40, 1),
  ('study', 'Study / home office', 'interior', 50, 1);

INSERT INTO `agents` (`slug`, `name`, `title`, `region`, `bio`, `badge`, `photo_path`, `sort_order`, `is_active`) VALUES
  ('eleanor-vance', 'Eleanor Vance', 'Senior agent', 'Sydney Eastern Suburbs',
   'Luxury residential across Sydney''s eastern suburbs and harbour precincts.',
   'Top Producer', 'assets/img/agent-eleanor-vance.jpg', 10, 1),
  ('julian-thorne', 'Julian Thorne', 'Sales consultant', 'Melbourne',
   'Inner-east and bayside listings across Melbourne.',
   NULL, 'assets/img/agent-julian-thorne.jpg', 20, 1),
  ('chloe-sterling', 'Chloe Sterling', 'Sales consultant', 'Geelong & Bellarine',
   'Coastal and lifestyle property on the Bellarine Peninsula.',
   NULL, 'assets/img/agent-chloe-sterling.jpg', 30, 1),
  ('marcus-wright', 'Marcus Wright', 'Sales consultant', 'Victoria',
   'Regional Victoria and growth corridors.',
   NULL, 'assets/img/agent-marcus-wright.jpg', 40, 1);

INSERT INTO `offices` (`name`, `address_line`, `city`, `region`, `postal_code`, `sort_order`, `is_active`) VALUES
  ('Sydney', '', 'Sydney', 'NSW', '', 10, 1),
  ('Melbourne', '', 'Melbourne', 'VIC', '', 20, 1);

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
  ('site_phone', ''),
  ('site_email', ''),
  ('site_name', 'Houdwell Garden'),
  ('site_logo_path', NULL),
  ('site_favicon_path', NULL),
  ('mail_from_name', NULL);

-- Listings: php scripts/apply-hougarden-manifest.php (or import hougarden-deploy.sql).
