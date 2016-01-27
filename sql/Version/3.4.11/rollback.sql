DELETE FROM `labels` WHERE `module` = 'Institutions' AND `field` = 'area_administrative_id';

ALTER TABLE `institutions` 
CHANGE COLUMN `postal_code` `postal_code` VARCHAR(20) NOT NULL DEFAULT '';

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1508';

-- security_functions
UPDATE `security_functions` SET `_execute`='Promotion.index|Promotion.add' WHERE `id`=1005;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2484';

-- config_item_options
DELETE FROM `config_item_options` WHERE `option_type` = 'authentication_type' AND `option` = 'Google';

-- config_items
UPDATE `config_items` SET `value` = 'Local' WHERE `type` = 'Authentication' AND `code` = 'authentication_type';

-- authentication_type_attributes
DROP TABLE `authentication_type_attributes`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2505';

-- institution_section_students
UPDATE `institution_section_students` 
INNER JOIN `z_2500_institution_section_students` 
	ON `z_2500_institution_section_students`.`student_id` = `institution_section_students`.`student_id`
	AND `z_2500_institution_section_students`.`institution_section_id` = `institution_section_students`.`institution_section_id`
	AND `z_2500_institution_section_students`.`education_grade_id` = `institution_section_students`.`education_grade_id`
SET `institution_section_students`.`id` = `z_2500_institution_section_students`.`id`;

DROP TABLE `z_2500_institution_section_students`;

-- security_groups
UPDATE `security_groups` INNER JOIN `z_2500_security_groups` ON `z_2500_security_groups`.`id` = `security_groups`.`id`
SET `security_groups`.`name` = `z_2500_security_groups`.`name`;

DROP TABLE `z_2500_security_groups`;

-- db_patches
DELETE FROM db_patches WHERE `issue` = 'PHPOE-2500';

UPDATE config_items SET value = '3.4.10' WHERE code = 'db_version';
