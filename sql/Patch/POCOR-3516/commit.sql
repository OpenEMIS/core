-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3516', NOW());


-- custom_field_types
CREATE TABLE `z_3516_custom_field_types`  LIKE `custom_field_types`;
INSERT INTO `z_3516_custom_field_types` SELECT * FROM `custom_field_types`;

UPDATE `custom_field_types` SET `id` = `id`+1 WHERE `id` >= 3 order by id desc;

INSERT INTO `custom_field_types` (`id`, `code`, `name`, `value`, `description`, `format`, `is_mandatory`, `is_unique`, `visible`)
VALUES (3, 'DECIMAL', 'Decimal', 'decimal_value', '', 'OpenEMIS', '1', '0', '1');


-- custom_field_values
CREATE TABLE `z_3516_custom_field_values`  LIKE `custom_field_values`;
INSERT INTO `z_3516_custom_field_values` SELECT * FROM `custom_field_values`;

ALTER TABLE `custom_field_values` ADD `decimal_value` VARCHAR(25) NULL DEFAULT NULL AFTER `number_value`;


-- institution_custom_field_values
CREATE TABLE `z_3516_institution_custom_field_values`  LIKE `institution_custom_field_values`;
INSERT INTO `z_3516_institution_custom_field_values` SELECT * FROM `institution_custom_field_values`;

ALTER TABLE `institution_custom_field_values` ADD `decimal_value` VARCHAR(25) NULL DEFAULT NULL AFTER `number_value`;