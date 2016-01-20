INSERT INTO `db_patches` VALUES ('PHPOE-2168', NOW());

-- SELECT * FROM institution_section_classes WHERE institution_section_id NOT IN (SELECT id FROM institution_sections);
DELETE FROM institution_section_classes WHERE NOT EXISTS (SELECT id FROM institution_sections WHERE institution_sections.id = institution_section_classes.institution_section_id);

-- SELECT * FROM institution_classes WHERE id NOT IN (SELECT institution_class_id FROM institution_section_classes);
DELETE FROM institution_classes WHERE NOT EXISTS (SELECT institution_class_id FROM institution_section_classes WHERE institution_section_classes.institution_class_id = institution_classes.id);

-- SELECT * FROM institution_class_students WHERE institution_class_id NOT IN (SELECT id FROM institution_classes);
DELETE FROM institution_class_students WHERE NOT EXISTS (SELECT id FROM institution_classes WHERE institution_classes.id = institution_class_students.institution_class_id);

-- SELECT * FROM institution_class_staff WHERE institution_class_id NOT IN (SELECT id FROM institution_classes);
DELETE FROM institution_class_staff WHERE NOT EXISTS (SELECT id FROM institution_classes WHERE institution_classes.id = institution_class_staff.institution_class_id);


INSERT INTO `db_patches` VALUES ('PHPOE-2433', NOW());

UPDATE security_functions SET _execute = concat(_execute, '|StudentUser.excel')  WHERE id = 1012;
UPDATE security_functions SET _execute = concat(_execute, '|StaffUser.excel')  WHERE id = 1016;

INSERT INTO `db_patches` VALUES ('PHPOE-2423', NOW());

-- backing up
CREATE TABLE `z2423_assessment_items` LIKE `assessment_items`;
INSERT INTO `z2423_assessment_items` SELECT * FROM `assessment_items` WHERE 1;
CREATE TABLE `z2423_assessment_item_results` LIKE `assessment_item_results`;
INSERT INTO `z2423_assessment_item_results` SELECT * FROM `assessment_item_results` WHERE 1;

-- deleting not visible assessment items and their associated results
DELETE FROM assessment_items WHERE visible = 0;
DELETE FROM assessment_item_results WHERE NOT EXISTS (
	SELECT 1 FROM assessment_items WHERE assessment_items.id = assessment_item_results.assessment_item_id
);

ALTER TABLE `assessment_items` DROP `visible`;

UPDATE config_items SET value = '3.4.10' WHERE code = 'db_version';
