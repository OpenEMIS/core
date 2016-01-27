ALTER TABLE `academic_periods` 
CHANGE COLUMN `editable` `available` CHAR(1) NOT NULL DEFAULT '1',
CHANGE COLUMN `visible` `visible` CHAR(1) NOT NULL DEFAULT '1',
DROP INDEX `parent_id` ,
DROP INDEX `editable` ,
DROP INDEX `visible` ,
DROP INDEX `current` ;

UPDATE `academic_periods`
LEFT JOIN `z_1916_academic_periods` ON `academic_periods`.`id` = `z_1916_academic_periods`.`id`
  SET `academic_periods`.`current` = `z_1916_academic_periods`.`current`,
    `academic_periods`.`available` = `z_1916_academic_periods`.`available`,
    `academic_periods`.`visible` = `z_1916_academic_periods`.`visible`
  WHERE `academic_periods`.`id` = `z_1916_academic_periods`.`id`;
DROP TABLE `z_1916_academic_periods`;

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1916';