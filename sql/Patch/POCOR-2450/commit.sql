--
-- POCOR-2450
--

-- db_patches
INSERT INTO `db_patches` VALUES('POCOR-2450', NOW());

-- backup tables
CREATE TABLE `z_2450_custom_field_types` LIKE `custom_field_types`;
INSERT INTO `z_2450_custom_field_types` SELECT * FROM `custom_field_types`;

INSERT INTO `custom_field_types` (`id`, `code`, `name`, `value`, `description`, `format`, `is_mandatory`, `is_unique`, `visible`) VALUES
(10, 'COORDINATES', 'Coordinates', 'text_value', '', 'OpenEMIS', 0, 0, 1);
