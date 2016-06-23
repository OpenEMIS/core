-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3093', NOW());

-- update NULL value on default record.
UPDATE `academic_periods`
SET `end_date` = '0000-00-00', 
`end_year` = '0'
WHERE `code` = 'All'
AND `name` = 'All Data';

-- update NULL value on user record.
UPDATE `academic_periods`
SET `end_date` = DATE_FORMAT(start_date ,'%Y-12-31')
WHERE `end_date` IS NULL;

UPDATE `academic_periods`
SET `end_year` = start_year
WHERE `end_year` IS NULL;

-- alter both field to not NULL
ALTER TABLE `academic_periods` CHANGE `end_date` `end_date` DATE NOT NULL;
ALTER TABLE `academic_periods` CHANGE `end_year` `end_year` INT(4) NOT NULL;