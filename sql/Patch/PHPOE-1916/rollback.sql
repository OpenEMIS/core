ALTER TABLE `academic_periods` 
CHANGE COLUMN `editable` `available` CHAR(1) NOT NULL DEFAULT '1';
