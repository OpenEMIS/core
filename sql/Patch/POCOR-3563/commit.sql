-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3563', NOW());

-- institution_subject_staff
ALTER TABLE `institution_subject_staff` ADD `start_date` DATE NULL AFTER `id`, ADD `end_date` DATE NULL AFTER `start_date`;
ALTER TABLE `institution_subject_staff` ADD `institution_id` INT(11) NOT NULL COMMENT 'links to institutions.id' AFTER `staff_id`, ADD INDEX (`institution_id`);

-- patch existing record start_date to created date
UPDATE `institution_subject_staff`
SET `start_date` = `created`;

-- patch existing record end_date to institution_staff end_date
UPDATE `institution_subject_staff` `ISS`
INNER JOIN `institution_staff` `IS` ON `ISS`.`staff_id` = `IS`.`staff_id`
SET `ISS`.`end_date` = `IS`.`end_date`;

-- patch existing record institution_id to institution_subject_id institution_id
UPDATE `institution_subject_staff` `ISS`
INNER JOIN `institution_subjects` `IS` ON `ISS`.`institution_subject_id` = `IS`.`id`
SET `ISS`.`institution_id` = `IS`.`institution_id`;

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('1ebef019-d3df-11e6-907e-525400b263eb', 'InstitutionSubjects', 'inactive_teachers', 'Institutions -> Subjects', 'Inactive Teachers', NULL, NULL, 1, NULL, NULL, 1, '2017-01-07 00:00:00'),
('74436ffe-d63e-11e6-ad42-525400b263eb', 'InstitutionSubjects', 'end_date', 'Institutions -> Subjects', 'End Date', NULL, NULL, 1, NULL, NULL, 1, '2017-01-07 00:00:00'),
('9c0c7533-d63e-11e6-ad42-525400b263eb', 'InstitutionSubjects', 'teacher_name', 'Institutions -> Subjects', 'Teacher Name', NULL, NULL, 1, NULL, NULL, 1, '2017-01-07 00:00:00'),
('f94ed6be-d63e-11e6-ad42-525400b263eb', 'InstitutionSubjects', 'start_date', 'Institutions -> Subjects', 'Start Date', NULL, NULL, 1, NULL, NULL, 1, '2017-01-07 00:00:00');
