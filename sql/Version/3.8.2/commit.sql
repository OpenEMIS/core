-- POCOR-2944
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-2944', NOW());

-- update institution_quality_visits
ALTER TABLE `institution_quality_visits` CHANGE `institution_subject_id` `institution_subject_id` INT(11) NULL COMMENT 'links to institution_subjects.id';
ALTER TABLE `institution_quality_visits` CHANGE `staff_id` `staff_id` INT(11) NULL COMMENT 'links to security_users.id';


-- POCOR-3457
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3457', NOW());

-- assessment_grading_options
ALTER TABLE `assessment_grading_options` ADD `description` TEXT NULL DEFAULT NULL AFTER `name`;


-- 3.8.2
UPDATE config_items SET value = '3.8.2' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
