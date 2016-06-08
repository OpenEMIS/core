-- code here
UPDATE `area_administratives` SET parent_id = -1 WHERE id = 8;
UPDATE `areas` SET parent_id = -1 WHERE id = 1;


-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3058';