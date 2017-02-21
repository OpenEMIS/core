-- POCOR-3550
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3550', NOW());

-- security_functions
UPDATE `security_functions`
SET
`_view` = 'index|view',
`_edit` = 'edit'
WHERE `security_functions`.`id` = 5020;


-- POCOR-3711
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3711', NOW());

UPDATE `security_functions` SET `order` = `order` + 1 WHERE `id` < 2000 and `order` > 1016;
UPDATE `security_functions` SET `order` = 1017 WHERE id = 1016;
UPDATE `security_functions` SET `order` = 1018 WHERE id = 1044;
UPDATE `security_functions` SET `order` = 1019 WHERE id = 1003;

UPDATE `security_functions`
SET
`controller` = 'Institutions',
`_view` = 'Staff.index|Staff.view',
`_edit` = 'Staff.edit',
`_add` = 'Staff.add|getInstitutionPositions',
`_delete` = 'Staff.remove',
`_execute` = 'Staff.excel'
WHERE `id` = 1016;

UPDATE `security_functions`
SET
`name` = 'Overview',
`controller` = 'Institutions',
`_view` = 'StaffUser.view',
`_edit` = 'StaffUser.edit|StaffUser.pull',
`_add` = NULL,
`_delete` = NULL,
`_execute` = 'StaffUser.excel'
WHERE `id` = 3000;


-- POCOR-3701
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3701', NOW());

-- assessments
RENAME TABLE `assessments` TO `z_3701_assessments`;

DROP TABLE IF EXISTS `assessments`;
CREATE TABLE IF NOT EXISTS `assessments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `excel_template_name` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `excel_template` longblob,
  `type` int(1) NOT NULL DEFAULT '1' COMMENT '1 -> Non-official, 2 -> Official',
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `education_grade_id` int(11) NOT NULL COMMENT 'links to education_grades.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `academic_period_id` (`academic_period_id`),
  INDEX `education_grade_id` (`education_grade_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the assessment template for a specific grade';

INSERT INTO `assessments` (`id`, `code`, `name`, `description`, `excel_template_name`, `excel_template`, `type`, `academic_period_id`, `education_grade_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `Assessments`.`id`, `Assessments`.`code`, `Assessments`.`name`, `Assessments`.`description`, `ExcelTemplates`.`file_name`, `ExcelTemplates`.`file_content`, `Assessments`.`type`, `Assessments`.`academic_period_id`, `Assessments`.`education_grade_id`, `Assessments`.`modified_user_id`, `Assessments`.`modified`, `Assessments`.`created_user_id`, `Assessments`.`created`
FROM `z_3701_assessments` AS `Assessments` LEFT JOIN `excel_templates` AS `ExcelTemplates` ON 1 = 1;

-- security_functions
UPDATE `security_functions` SET `_execute` = 'Assessments.download' WHERE `id` = 5010;

-- excel_templates
RENAME TABLE `excel_templates` TO `z_3701_excel_templates`;

-- labels
DELETE FROM `labels` WHERE `id` = 'ad8fa33a-c0d8-11e6-90e8-525400b263eb';

-- security_functions
DELETE FROM `security_functions` WHERE `id` = 5059;


-- POCOR-2828
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-2828', NOW());

UPDATE `security_functions` SET `_add`='StaffUser.add|getUniqueOpenemisId' WHERE `id`='1044';
UPDATE `security_functions` SET `_add`='Staff.add|getInstitutionPositions' WHERE `id`='1016';
UPDATE `security_functions` SET `_view`='Directories.index|Directories.view', `_edit`='Directories.edit|Directories.pull', `_add`='Directories.add', `_delete`='Directories.remove' WHERE `id`='7000';

INSERT INTO `external_data_source_attributes` (`id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id`)
SELECT * FROM (SELECT '653f3552-d32e-11e6-9166-525400b263eb', 'OpenEMIS Identity', 'address_mapping' as field, 'address_mapping', 'address', NOW(), 1) as tmp
WHERE NOT EXISTS (
    SELECT `id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id` FROM `external_data_source_attributes` WHERE `external_data_source_type` = 'OpenEMIS Identity' AND `attribute_field` = 'address_mapping'
);

INSERT INTO `external_data_source_attributes` (`id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id`)
SELECT * FROM (SELECT '858682fb-d583-11e6-87d6-525400b263eb', 'OpenEMIS Identity', 'postal_mapping' as field, 'postal_mapping', 'postal_code', NOW(), 1) as tmp
WHERE NOT EXISTS (
    SELECT `id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id` FROM `external_data_source_attributes` WHERE `external_data_source_type` = 'OpenEMIS Identity' AND `attribute_field` = 'postal_mapping'
);

INSERT INTO `external_data_source_attributes` (`id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id`)
SELECT * FROM (SELECT '51528c40-d331-11e6-9166-525400b263eb', 'Custom', 'address_mapping' as field, 'address_mapping', '', NOW(), 1) as tmp
WHERE NOT EXISTS (
    SELECT `id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id` FROM `external_data_source_attributes` WHERE `external_data_source_type` = 'Custom' AND `attribute_field` = 'address_mapping'
);

INSERT INTO `external_data_source_attributes` (`id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id`)
SELECT * FROM (SELECT '615a0770-d584-11e6-87d6-525400b263eb', 'Custom', 'postal_mapping' as field, 'postal_mapping', '', NOW(), 1) as tmp
WHERE NOT EXISTS (
    SELECT `id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `created`, `created_user_id` FROM `external_data_source_attributes` WHERE `external_data_source_type` = 'Custom' AND `attribute_field` = 'postal_mapping'
);


-- 3.8.6
UPDATE config_items SET value = '3.8.6' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
