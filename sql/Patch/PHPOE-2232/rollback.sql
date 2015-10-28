UPDATE `field_options` SET `plugin` = 'Staff' WHERE `field_options`.`code` = 'StaffPositionGrades';
UPDATE `field_options` SET `plugin` = 'Staff' WHERE `field_options`.`code` = 'StaffPositionTitles';

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2232';
