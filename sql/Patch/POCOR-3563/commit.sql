-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3563', NOW());

-- temp_institution_subject_staff
DROP TABLE IF EXISTS `temp_institution_subject_staff`;
CREATE TABLE `temp_institution_subject_staff` LIKE `institution_subject_staff`;

ALTER TABLE `temp_institution_subject_staff` ADD `start_date` DATE NULL AFTER `id`, ADD `end_date` DATE NULL AFTER `start_date`;
ALTER TABLE `temp_institution_subject_staff` ADD `institution_id` INT(11) NOT NULL COMMENT 'links to institutions.id' AFTER `staff_id`, ADD INDEX (`institution_id`);

INSERT INTO `temp_institution_subject_staff`
SELECT `ISS`.`id`, `ISS`.`created`, `IST`.`end_date`, `IST`.`staff_id`, `IST`.`institution_id`, `IS`.`id`, 
`ISS`.`modified_user_id`, `ISS`.`modified`, `ISS`.`created_user_id`, `ISS`.`created`
FROM `institution_subject_staff` `ISS`
INNER JOIN `institution_subjects` `IS` 
    ON `ISS`.`institution_subject_id` = `IS`.`id`
INNER JOIN `institution_staff` `IST` 
    ON (
        `ISS`.`staff_id` = `IST`.`staff_id`
        AND `IS`.`institution_id` = `IST`.`institution_id`
    )
GROUP BY `IST`.`staff_id`, `IST`.`institution_id`, `IS`.`id`;

RENAME TABLE `institution_subject_staff` TO `z_3563_institution_subject_staff`;

RENAME TABLE `temp_institution_subject_staff` TO `institution_subject_staff`;

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('1ebef019-d3df-11e6-907e-525400b263eb', 'InstitutionSubjects', 'past_teachers', 'Institutions -> Subjects', 'Past Teachers', NULL, NULL, 1, NULL, NULL, 1, '2017-01-07 00:00:00'),
('74436ffe-d63e-11e6-ad42-525400b263eb', 'InstitutionSubjects', 'end_date', 'Institutions -> Subjects', 'End Date', NULL, NULL, 1, NULL, NULL, 1, '2017-01-07 00:00:00'),
('9c0c7533-d63e-11e6-ad42-525400b263eb', 'InstitutionSubjects', 'teacher_name', 'Institutions -> Subjects', 'Teacher Name', NULL, NULL, 1, NULL, NULL, 1, '2017-01-07 00:00:00'),
('f94ed6be-d63e-11e6-ad42-525400b263eb', 'InstitutionSubjects', 'start_date', 'Institutions -> Subjects', 'Start Date', NULL, NULL, 1, NULL, NULL, 1, '2017-01-07 00:00:00');
