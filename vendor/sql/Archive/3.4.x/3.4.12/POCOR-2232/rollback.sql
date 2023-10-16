UPDATE `field_options` SET `plugin` = 'Staff' WHERE `field_options`.`code` = 'StaffPositionGrades';
UPDATE `field_options` SET `plugin` = 'Staff' WHERE `field_options`.`code` = 'StaffPositionTitles';

UPDATE `field_options` SET `plugin` = 'Students' WHERE `field_options`.`code` = 'StudentDropoutReasons';

DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2232';
