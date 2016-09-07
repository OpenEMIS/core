-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3357', NOW());

-- add `institution_sector_id` column to `institution_providers` table
ALTER TABLE `institution_providers`
ADD `institution_sector_id` int(11) NOT NULL COMMENT 'links to institution_sector.id'
AFTER `default`;

-- link existing providers to default or first sector
UPDATE `institution_providers`
SET `institution_sector_id` = IFNULL((SELECT `id` FROM `institution_sectors` WHERE `default` = 1), 1);