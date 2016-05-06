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

UPDATE `custom_modules` SET `supported_field_types` = CONCAT(`supported_field_types`, ',COORDINATES') WHERE `model` = 'Institution.Institutions';
UPDATE `custom_modules` SET `supported_field_types` = CONCAT(`supported_field_types`, ',COORDINATES') WHERE `model` = 'Student.Students';
UPDATE `custom_modules` SET `supported_field_types` = CONCAT(`supported_field_types`, ',COORDINATES') WHERE `model` = 'Staff.Staff';
UPDATE `custom_modules` SET `supported_field_types` = CONCAT(`supported_field_types`, ',COORDINATES') WHERE `model` = 'Institution.InstitutionInfrastructures';
