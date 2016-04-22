-- db_patches
INSERT INTO `db_patches` VALUES('POCOR-2449', NOW());

-- custom_field_types
INSERT INTO `custom_field_types` (`code`, `name`, `value`, `description`, `format`, `is_mandatory`, `is_unique`, `visible`) VALUES
('FILE', 'File', 'file', '', 'OpenEMIS', 1, 0, 1);

-- custom_modules
UPDATE `custom_modules` SET `supported_field_types` = 'TEXT,NUMBER,TEXTAREA,DROPDOWN,CHECKBOX,TABLE,STUDENT_LIST,FILE' WHERE `model` = 'Institution.Institutions';
UPDATE `custom_modules` SET `supported_field_types` = 'TEXT,NUMBER,TEXTAREA,DROPDOWN,CHECKBOX,TABLE,FILE' WHERE `model` = 'Student.Students';
UPDATE `custom_modules` SET `supported_field_types` = 'TEXT,NUMBER,TEXTAREA,DROPDOWN,CHECKBOX,TABLE,FILE' WHERE `model` = 'Staff.Staff';
UPDATE `custom_modules` SET `supported_field_types` = 'TEXT,NUMBER,TEXTAREA,DROPDOWN,CHECKBOX,TABLE,FILE' WHERE `model` = 'Institution.InstitutionInfrastructures';

-- custom_field_values
RENAME TABLE `custom_field_values` TO `z_2449_custom_field_values`;

DROP TABLE IF EXISTS `custom_field_values`;
CREATE TABLE IF NOT EXISTS `custom_field_values` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `textarea_value` text,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `file` longblob DEFAULT NULL,
  `custom_field_id` int(11) NOT NULL,
  `custom_record_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `custom_field_id` (`custom_field_id`),
  INDEX `custom_record_id` (`custom_record_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `custom_field_values` (`id`, `text_value`, `number_value`, `textarea_value`, `date_value`, `time_value`, `file`, `custom_field_id`, `custom_record_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `text_value`, `number_value`, `textarea_value`, `date_value`, `time_value`, NULL, `custom_field_id`, `custom_record_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_2449_custom_field_values`;
