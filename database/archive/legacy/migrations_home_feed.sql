-- Homepage feed tabs: Recommend / Just watched / Saved (admin-controlled per property)
ALTER TABLE `properties`
  ADD COLUMN `is_home_recommend` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_featured`,
  ADD COLUMN `is_home_watched` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_home_recommend`,
  ADD COLUMN `is_home_saved` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_home_watched`;

UPDATE `properties` SET `is_home_recommend` = 1 WHERE `is_featured` = 1 AND `is_home_recommend` = 0;
