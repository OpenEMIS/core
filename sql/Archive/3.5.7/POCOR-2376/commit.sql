-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-2376', NOW());

-- institution_student_admission
ALTER TABLE `institution_student_admission` 
ADD COLUMN `new_education_grade_id` INT(11) NULL COMMENT '' AFTER `education_grade_id`,
ADD INDEX `new_education_grade_id` (`new_education_grade_id`);

UPDATE `institution_student_admission`
SET `new_education_grade_id` = `education_grade_id`
WHERE `type` = 2;

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'TransferApprovals', 'new_education_grade_id', 'Institutions -> Transfer Approvals', 'Education Grade', 1, 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'TransferRequests', 'new_education_grade_id', 'Institutions -> Transfer Requests', 'Education Grade', 1, 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'TransferApprovals', 'previous_institution_id', 'Institutions -> Transfer Approvals', 'Institution', 1, 1, NOW());

