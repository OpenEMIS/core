-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3371';

-- staff_qualifications
ALTER TABLE `staff_qualifications` CHANGE `graduate_year` `graduate_year` INT(4) NOT NULL;