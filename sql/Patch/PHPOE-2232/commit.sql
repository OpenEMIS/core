INSERT INTO `db_patches` VALUES ('PHPOE-2232', NOW());

UPDATE `field_options` SET `plugin` = 'Institution' WHERE `field_options`.`code` = 'StaffPositionGrades';
UPDATE `field_options` SET `plugin` = 'Institution' WHERE `field_options`.`code` = 'StaffPositionTitles';