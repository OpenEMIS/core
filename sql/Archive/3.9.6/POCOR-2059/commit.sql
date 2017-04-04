-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-2059', NOW());

ALTER TABLE `institution_subject_students` ADD INDEX(`id`);
ALTER TABLE `assessment_item_results` ADD INDEX(`id`);
