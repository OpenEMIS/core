-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3371', NOW());

-- staff_qualifications
ALTER TABLE `staff_qualifications` CHANGE `graduate_year` `graduate_year` INT(4) NULL;