--
-- POCOR-2450
--

-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES('POCOR-2450', NOW());

INSERT INTO `custom_field_types` (`id`, `code`, `name`, `value`, `description`, `format`, `is_mandatory`, `is_unique`, `visible`) VALUES
(10, 'COORDINATES', 'Coordinates', 'text_value', '', 'OpenEMIS', 1, 0, 1);

-- backup tables
CREATE TABLE `z_2450_custom_modules` LIKE `custom_modules`;
INSERT INTO `z_2450_custom_modules` SELECT * FROM `custom_modules`;

UPDATE `custom_modules` SET `supported_field_types`='TEXT,NUMBER,TEXTAREA,DROPDOWN,CHECKBOX,TABLE,DATE,TIME,STUDENT_LIST,COORDINATES' WHERE `code`='Institution';
UPDATE `custom_modules` SET `supported_field_types`='TEXT,NUMBER,TEXTAREA,DROPDOWN,CHECKBOX,TABLE,DATE,TIME,COORDINATES' WHERE `code`='Student';
UPDATE `custom_modules` SET `supported_field_types`='TEXT,NUMBER,TEXTAREA,DROPDOWN,CHECKBOX,TABLE,DATE,TIME,COORDINATES' WHERE `code`='Staff';
UPDATE `custom_modules` SET `supported_field_types`='TEXT,NUMBER,TEXTAREA,DROPDOWN,CHECKBOX,TABLE,DATE,TIME,COORDINATES' WHERE `code`='Infrastructure';
UPDATE `custom_modules` SET `supported_field_types`='TEXT,NUMBER,DROPDOWN,COORDINATES' WHERE `code`='Institution > Students';
