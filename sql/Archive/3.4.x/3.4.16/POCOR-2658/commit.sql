-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2658', NOW());

-- labels
UPDATE `labels` SET `field_name` = 'Area Administrative' WHERE `module` = 'Institutions' AND `field` = 'area_administrative_id';
UPDATE `labels` SET `field_name` = 'Area Education' WHERE `module` = 'Institutions' AND `field` = 'area_id';