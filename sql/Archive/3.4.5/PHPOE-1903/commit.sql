INSERT INTO `db_patches` VALUES ('PHPOE-1903', NOW());

-- labels
UPDATE `labels` SET `field_name`='Institution' WHERE `module` = 'InstitutionShifts' AND `field` = 'location_institution_id';
