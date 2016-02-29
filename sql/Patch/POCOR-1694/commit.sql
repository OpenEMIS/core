-- 
-- POCOR-1694
-- 

-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-1694', NOW());

ALTER TABLE `institution_classes` RENAME `z_1694_institution_classes`;
CREATE TABLE `institution_subjects` LIKE `z_1694_institution_classes`;
INSERT INTO `institution_subjects` SELECT * FROM `z_1694_institution_classes`;

ALTER TABLE `institution_class_staff` RENAME `z_1694_institution_class_staff`;
CREATE TABLE `institution_subject_staff` LIKE `z_1694_institution_class_staff`;
INSERT INTO `institution_subject_staff` SELECT * FROM `z_1694_institution_class_staff`;
ALTER TABLE `institution_subject_staff` CHANGE `institution_class_id` `institution_subject_id` INT(11) NOT NULL;

ALTER TABLE `institution_class_students` RENAME `z_1694_institution_class_students`;
CREATE TABLE `institution_subject_students` LIKE `z_1694_institution_class_students`;
INSERT INTO `institution_subject_students` SELECT * FROM `z_1694_institution_class_students`;
ALTER TABLE `institution_subject_students` CHANGE `institution_class_id` `institution_subject_id` INT(11) NOT NULL;

ALTER TABLE `institution_section_classes` RENAME `z_1694_institution_section_classes`;
CREATE TABLE `institution_class_subjects` LIKE `z_1694_institution_section_classes`;
INSERT INTO `institution_class_subjects` SELECT * FROM `z_1694_institution_section_classes`;
ALTER TABLE `institution_class_subjects` 
	CHANGE `institution_class_id` `institution_subject_id` INT(11) NOT NULL,
	CHANGE `institution_section_id` `institution_class_id` INT(11) NOT NULL;

ALTER TABLE `institution_sections` RENAME `z_1694_institution_sections`;
CREATE TABLE `institution_classes` LIKE `z_1694_institution_sections`;
INSERT INTO `institution_classes` SELECT * FROM `z_1694_institution_sections`;
ALTER TABLE `institution_classes` CHANGE `section_number` `class_number` INT(11) NULL DEFAULT NULL;

ALTER TABLE `institution_section_grades` RENAME `z_1694_institution_section_grades`;
CREATE TABLE `institution_class_grades` LIKE `z_1694_institution_section_grades`;
INSERT INTO `institution_class_grades` SELECT * FROM `z_1694_institution_section_grades`;
ALTER TABLE `institution_class_grades` CHANGE `institution_section_id` `institution_class_id` INT(11) NOT NULL;

ALTER TABLE `institution_section_students` RENAME `z_1694_institution_section_students`;
CREATE TABLE `institution_class_students` LIKE `z_1694_institution_section_students`;
INSERT INTO `institution_class_students` SELECT * FROM `z_1694_institution_section_students`;
ALTER TABLE `institution_class_students` CHANGE `institution_section_id` `institution_class_id` INT(11) NOT NULL;

