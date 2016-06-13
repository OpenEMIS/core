-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3058', NOW());


-- code here
UPDATE `area_administratives` SET `parent_id` = NULL WHERE `parent_id` = -1;
UPDATE `areas` SET `parent_id` = NULL WHERE `parent_id` = -1;