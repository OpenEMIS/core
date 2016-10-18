-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3387', NOW());

-- field_options
-- change order of Sectors
UPDATE `field_options`
SET `order` = 5
WHERE `id` = 5;

-- change order of Providers
UPDATE `field_options`
SET `order` = 6
WHERE `id` = 4;
