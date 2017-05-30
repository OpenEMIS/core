-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3457', NOW());

-- assessment_grading_options
ALTER TABLE `assessment_grading_options` ADD `description` TEXT NULL DEFAULT NULL AFTER `name`;

-- examination_grading_options
ALTER TABLE `examination_grading_options` ADD `description` TEXT NULL DEFAULT NULL AFTER `name`;