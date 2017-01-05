-- update institution_quality_visits
ALTER TABLE `institution_quality_visits` CHANGE `institution_subject_id` `institution_subject_id` INT(11) NOT NULL COMMENT 'links to institution_subjects.id';
ALTER TABLE `institution_quality_visits` CHANGE `staff_id` `staff_id` INT(11) NOT NULL COMMENT 'links to security_users.id';

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-2944';
