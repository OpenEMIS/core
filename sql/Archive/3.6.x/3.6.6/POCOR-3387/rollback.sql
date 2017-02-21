-- field_options
-- restore order of Providers and Sectors
UPDATE `field_options`
SET `order` = 6
WHERE `id` = 5;

UPDATE `field_options`
SET `order` = 5
WHERE `id` = 4;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3387';
