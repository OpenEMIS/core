INSERT INTO `db_patches` VALUES ('POCOR-2232', NOW());

UPDATE `field_options` SET `plugin` = 'Institution' WHERE `field_options`.`code` = 'StaffPositionGrades';
UPDATE `field_options` SET `plugin` = 'Institution' WHERE `field_options`.`code` = 'StaffPositionTitles';

UPDATE `field_options` SET `plugin` = 'FieldOption' WHERE `field_options`.`code` = 'StudentDropoutReasons';