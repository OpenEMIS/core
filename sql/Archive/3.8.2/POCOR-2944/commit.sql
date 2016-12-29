-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-2944', NOW());

-- update institution_quality_visits
ALTER TABLE `institution_quality_visits` CHANGE `institution_subject_id` `institution_subject_id` INT(11) NULL COMMENT 'links to institution_subjects.id';
ALTER TABLE `institution_quality_visits` CHANGE `staff_id` `staff_id` INT(11) NULL COMMENT 'links to security_users.id';