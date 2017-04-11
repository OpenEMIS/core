-- code here
UPDATE `area_administratives` SET `parent_id` = -1 WHERE `parent_id` IS NULL;
UPDATE `areas` SET `parent_id` = -1 WHERE `parent_id` IS NULL;


-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3058';