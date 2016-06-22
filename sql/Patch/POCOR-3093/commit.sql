-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3093', NOW());

UPDATE `academic_periods`
SET `end_date` = '0000-00-00', `end_year` = '0'
WHERE `code` = 'All'
AND `name` = 'All Data';

ALTER TABLE `academic_periods` CHANGE `end_date` `end_date` DATE NOT NULL;
ALTER TABLE `academic_periods` CHANGE `end_year` `end_year` INT(4) NOT NULL;