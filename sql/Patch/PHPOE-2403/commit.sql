--
-- PHPOE-2403
--

INSERT INTO `db_patches` VALUES ('PHPOE-2403', NOW());

DROP TABLE IF EXISTS `z_2366_import_mapping`;
DROP TABLE IF EXISTS `z_2359_import_mapping`;
DROP TABLE IF EXISTS `z_1463_import_mapping`;

CREATE TABLE `z_2403_import_mapping` LIKE `import_mapping`;
INSERT INTO `z_2403_import_mapping` SELECT * FROM `import_mapping`;

UPDATE `import_mapping` 
	SET `id`=80
	WHERE `model`='User.Users' AND `column_name`='account_type';
UPDATE `import_mapping` 
	SET `id`=79
	WHERE `model`='User.Users' AND `column_name`='birthplace_area_id';
UPDATE `import_mapping` 
	SET `id`=78
	WHERE `model`='User.Users' AND `column_name`='address_area_id';
UPDATE `import_mapping` 
	SET `id`=77
	WHERE `model`='User.Users' AND `column_name`='postal_code';
UPDATE `import_mapping` 
	SET `id`=76
	WHERE `model`='User.Users' AND `column_name`='address';
UPDATE `import_mapping` 
	SET `id`=75
	WHERE `model`='User.Users' AND `column_name`='date_of_birth';
UPDATE `import_mapping` 
	SET `id`=74
	WHERE `model`='User.Users' AND `column_name`='gender_id';
UPDATE `import_mapping` 
	SET `id`=73
	WHERE `model`='User.Users' AND `column_name`='preferred_name';
UPDATE `import_mapping` 
	SET `id`=72
	WHERE `model`='User.Users' AND `column_name`='last_name';
UPDATE `import_mapping` 
	SET `id`=71
	WHERE `model`='User.Users' AND `column_name`='third_name';
UPDATE `import_mapping` 
	SET `id`=70
	WHERE `model`='User.Users' AND `column_name`='middle_name';
UPDATE `import_mapping` 
	SET `id`=69
	WHERE `model`='User.Users' AND `column_name`='first_name';
UPDATE `import_mapping` 
	SET `id`=68
	WHERE `model`='User.Users' AND `column_name`='openemis_no';

INSERT INTO `import_mapping` 
(`id`, `model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) VALUES
(67, 'Institution.Students', 'class', 'Code', 5, 2, 'Institution', 'InstitutionSections', 'id');

ALTER TABLE `import_mapping`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=81;


CREATE TABLE `z_2403_labels` LIKE `labels`;
INSERT INTO `z_2403_labels` SELECT * FROM `labels`;

INSERT INTO `labels` 
	(`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES 
	(UUID(), 'Imports', 'period_code', 'Institution -> Students -> Import', 'Academic Period Code', NULL, NULL, '1', NULL, NULL, '0', NOW()),
	(UUID(), 'Imports', 'institution_sections_code', 'Institution -> Students -> Import', 'Class Code', NULL, NULL, '1', NULL, NULL, '0', NOW()),
	(UUID(), 'Imports', 'InstitutionSections', 'Institution -> Students -> Import', 'Class', NULL, NULL, '1', NULL, NULL, '0', NOW()),
	(UUID(), 'Imports', 'institution_name', 'Institution -> Students -> Import', 'Institution Name', NULL, NULL, '1', NULL, NULL, '0', NOW())
	;

