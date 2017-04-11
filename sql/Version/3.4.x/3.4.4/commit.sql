--
-- PHPOE-2403
--

INSERT INTO `db_patches` VALUES ('PHPOE-2403', NOW());

DROP TABLE IF EXISTS `z_2366_import_mapping`;
DROP TABLE IF EXISTS `z_2359_import_mapping`;
DROP TABLE IF EXISTS `z_1463_import_mapping`;

CREATE TABLE `z_2403_import_mapping` LIKE `import_mapping`;
INSERT INTO `z_2403_import_mapping` SELECT * FROM `import_mapping`;

update `import_mapping` set `id` = `id`+1000 where `id` > 66;
update `import_mapping` set `id` = `id`-999 where `id` > 1000;

INSERT INTO `import_mapping` 
(`id`, `model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) VALUES
(67, 'Institution.Students', 'class', 'Code (Optional)', 5, 2, 'Institution', 'InstitutionSections', 'id');

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

-- clean up backup tables
DROP TABLE IF EXISTS `z_2250_workflow_transitions`;
DROP TABLE IF EXISTS `z_2298_security_functions`;
DROP TABLE IF EXISTS `z_2193_guardian_activities`;
DROP TABLE IF EXISTS `z_2193_staff_activities`;
DROP TABLE IF EXISTS `z_2193_student_activities`;

UPDATE config_items SET value = '3.4.4' WHERE code = 'db_version';
