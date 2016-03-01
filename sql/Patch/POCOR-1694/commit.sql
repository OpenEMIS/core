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
ALTER TABLE `institution_subject_students` 
	CHANGE `institution_class_id` `institution_subject_id` INT(11) NOT NULL,
	CHANGE `institution_section_id` `institution_class_id` INT(11) NOT NULL;

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

ALTER TABLE `institution_quality_rubrics` 
	CHANGE `institution_class_id` `institution_subject_id` INT(11) NOT NULL,
	CHANGE `institution_section_id` `institution_class_id` INT(11) NOT NULL;

ALTER TABLE `institution_quality_visits` CHANGE `institution_class_id` `institution_subject_id` INT(11) NOT NULL;

UPDATE `labels` SET `field`='subjects' WHERE `module`='InstitutionSections' AND `field`='classes';
UPDATE `labels` SET `field`='institution_subject_id'
WHERE 
 	`module` IN (
 		'Absences',
 		'StaffClasses',
 		'StaffAbsences',
 		'StudentClasses',
 		'InstitutionRubrics',
 		'InstitutionQualityVisits',
 		'InstitutionStudentAbsences'
 	) AND `field`='institution_class_id';
UPDATE `labels` SET `field`='institution_class_id'
WHERE 
 	`module` IN (
		'Absences',
		'StaffAbsences',
		'StudentClasses',
		'StudentSections',
		'InstitutionRubrics',
		'InstitutionStudentAbsences'
 	) AND `field`='institution_section_id';
UPDATE `labels` SET `field`='class'
WHERE 
 	`module` IN (
		'InstitutionStudentAbsences',
		'StudentBehaviours',
		'Students'
	) AND `field`='section';
UPDATE `labels` SET `field`='institution_classes_code' WHERE `module`='Imports' AND `field`='institution_sections_code';
UPDATE `labels` SET `field`='InstitutionClasses' WHERE `module`='Imports' AND `field`='InstitutionSections';
UPDATE `labels` SET `field`='number_of_classes' WHERE `module`='InstitutionSections' AND `field`='number_of_sections';
UPDATE `labels` SET `field`='institution_class' WHERE `module`='StaffClasses' AND `field`='institution_section';
UPDATE `labels` SET `field`='select_class' WHERE `module`='Absences' AND `field`='select_section';
UPDATE `labels` SET `module`='InstitutionSubjects' WHERE `module`='InstitutionClasses';
UPDATE `labels` SET `module`='StaffSubjects' WHERE `module`='StaffClasses';
UPDATE `labels` SET `module`='StudentSubjects' WHERE `module`='StudentClasses';
UPDATE `labels` SET `module`='InstitutionClasses' WHERE `module`='InstitutionSections';
UPDATE `labels` SET `module`='StudentClasses' WHERE `module`='StudentSections';

UPDATE `import_mapping` SET `lookup_model`='InstitutionClasses' WHERE `id`=66;

UPDATE `security_functions` SET `_view`='AllClasses.index|AllClasses.view|Classes.index|Classes.view', `_edit`='AllClasses.edit|Classes.edit', `_add`='Classes.add', `_delete`='Classes.remove', `_execute`=NULL WHERE `id`='1007';
UPDATE `security_functions` SET `_view`='Classes.index|Classes.view', `_edit`='Classes.edit', `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='1008';
UPDATE `security_functions` SET `_view`='AllSubjects.index|AllSubjects.view|Subjects.index|Subjects.view', `_edit`='AllSubjects.edit|Subjects.edit', `_add`='Subjects.add', `_delete`='Subjects.remove', `_execute`=NULL WHERE `id`='1009';
UPDATE `security_functions` SET `_view`='Subjects.index|Subjects.view', `_edit`='Subjects.edit', `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='1010';
UPDATE `security_functions` SET `_view`='Classes.index|Classes.view', `_edit`=NULL, `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='2012';
UPDATE `security_functions` SET `_view`='Subjects.index|Subjects.view', `_edit`=NULL, `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='2013';
UPDATE `security_functions` SET `_view`='Classes.index|Classes.view', `_edit`=NULL, `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='3013';
UPDATE `security_functions` SET `_view`='Subjects.index|Subjects.view', `_edit`=NULL, `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='3014';
UPDATE `security_functions` SET `_view`='StudentClasses.index', `_edit`=NULL, `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='7011';
UPDATE `security_functions` SET `_view`='StaffClasses.index', `_edit`=NULL, `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='7022';
UPDATE `security_functions` SET `_view`='StaffSubjects.index', `_edit`=NULL, `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='7023';

-- 
-- END POCOR-1694
-- 
