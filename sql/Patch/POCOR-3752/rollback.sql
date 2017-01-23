ALTER TABLE `competencies` CHANGE `max` `max` DECIMAL(4,2) NOT NULL DEFAULT '10.00';
ALTER TABLE `competencies` CHANGE `min` `min` DECIMAL(4,2) NOT NULL DEFAULT '10.00';
ALTER TABLE `staff_appraisals_competencies` CHANGE `rating` `rating` DECIMAL(4,2) NULL DEFAULT NULL;
ALTER TABLE `staff_appraisals` CHANGE `final_rating` `final_rating` DECIMAL(4,2) NOT NULL;

ALTER TABLE `staff_appraisals` DROP `file_name`;
ALTER TABLE `staff_appraisals` DROP `file_content`;

-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3752';

