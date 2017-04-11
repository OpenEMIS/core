-- POCOR-2733
-- Restore table
DROP TABLE IF EXISTS `staff_leaves`;
RENAME TABLE `z_2733_staff_leaves` TO `staff_leaves`;

DROP TABLE IF EXISTS `institution_surveys`;
RENAME TABLE `z_2733_institution_surveys` TO `institution_surveys`;

DROP TABLE IF EXISTS `workflow_records`;
RENAME TABLE `z_2733_workflow_records` TO `workflow_records`;

-- institution_student_surveys
ALTER TABLE `institution_student_surveys` DROP `parent_form_id`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2733';


-- 
-- POCOR-1694
-- 

DROP TABLE IF EXISTS `institution_class_subjects`;
ALTER TABLE `z_1694_institution_section_classes` RENAME `institution_section_classes`;

DROP TABLE IF EXISTS `institution_classes`;
ALTER TABLE `z_1694_institution_sections` RENAME `institution_sections`;

DROP TABLE IF EXISTS `institution_class_grades`;
ALTER TABLE `z_1694_institution_section_grades` RENAME `institution_section_grades`;

DROP TABLE IF EXISTS `institution_class_students`;
ALTER TABLE `z_1694_institution_section_students` RENAME `institution_section_students`;

DROP TABLE IF EXISTS `institution_subjects`;
ALTER TABLE `z_1694_institution_classes` RENAME `institution_classes`;

DROP TABLE IF EXISTS `institution_subject_staff`;
ALTER TABLE `z_1694_institution_class_staff` RENAME `institution_class_staff`;

DROP TABLE IF EXISTS `institution_subject_students`;
ALTER TABLE `z_1694_institution_class_students` RENAME `institution_class_students`;

ALTER TABLE `institution_quality_rubrics` 
	CHANGE `institution_class_id` `institution_section_id` INT(11) NOT NULL,
	CHANGE `institution_subject_id` `institution_class_id` INT(11) NOT NULL;

ALTER TABLE `institution_quality_visits` CHANGE `institution_subject_id` `institution_class_id` INT(11) NOT NULL;

UPDATE `labels` SET `module`='InstitutionSections' WHERE `module`='InstitutionClasses';
UPDATE `labels` SET `module`='StudentSections' WHERE `module`='StudentClasses';
UPDATE `labels` SET `module`='InstitutionClasses' WHERE `module`='InstitutionSubjects';
UPDATE `labels` SET `module`='StaffClasses' WHERE `module`='StaffSubjects';
UPDATE `labels` SET `module`='StudentClasses' WHERE `module`='StudentSubjects';
UPDATE `labels` SET `field`='institution_section_id'
WHERE 
 	`module` IN (
		'Absences',
		'StaffAbsences',
		'StudentClasses',
		'StudentSections',
		'InstitutionRubrics',
		'InstitutionStudentAbsences'
 	) AND `field`='institution_class_id';
UPDATE `labels` SET `field`='section'
WHERE 
 	`module` IN (
		'InstitutionStudentAbsences',
		'StudentBehaviours',
		'Students'
	) AND `field`='class';
UPDATE `labels` SET `field`='institution_sections_code' WHERE `module`='Imports' AND `field`='institution_classes_code';
UPDATE `labels` SET `field`='InstitutionSections' WHERE `module`='Imports' AND `field`='InstitutionClasses';
UPDATE `labels` SET `field`='number_of_sections' WHERE `module`='InstitutionSections' AND `field`='number_of_classes';
UPDATE `labels` SET `field`='institution_section' WHERE `module`='StaffClasses' AND `field`='institution_class';
UPDATE `labels` SET `field`='select_section' WHERE `module`='Absences' AND `field`='select_class';
UPDATE `labels` SET `field`='subjects' WHERE `module`='InstitutionSections' AND `field`='classes';
UPDATE `labels` SET `field`='institution_class_id'
WHERE 
 	`module` IN (
 		'Absences',
 		'StaffClasses',
 		'StaffAbsences',
 		'StudentClasses',
 		'InstitutionRubrics',
 		'InstitutionQualityVisits',
 		'InstitutionStudentAbsences'
 	) AND `field`='institution_subject_id';

UPDATE `import_mapping` SET `lookup_model`='InstitutionSections' WHERE `id`=66;

UPDATE `security_functions` SET `_view`='AllClasses.index|AllClasses.view|Sections.index|Sections.view', `_edit`='AllClasses.edit|Sections.edit', `_add`='Sections.add', `_delete`='Sections.remove', `_execute`=NULL WHERE `id`='1007';
UPDATE `security_functions` SET `_view`='Sections.index|Sections.view', `_edit`='Sections.edit', `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='1008';
UPDATE `security_functions` SET `_view`='AllSubjects.index|AllSubjects.view|Classes.index|Classes.view', `_edit`='AllSubjects.edit|Classes.edit', `_add`='Classes.add', `_delete`='Classes.remove', `_execute`=NULL WHERE `id`='1009';
UPDATE `security_functions` SET `_view`='Classes.index|Classes.view', `_edit`='Classes.edit', `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='1010';
UPDATE `security_functions` SET `_view`='Sections.index|Sections.view', `_edit`=NULL, `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='2012';
UPDATE `security_functions` SET `_view`='Classes.index|Classes.view', `_edit`=NULL, `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='2013';
UPDATE `security_functions` SET `_view`='Sections.index|Sections.view', `_edit`=NULL, `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='3013';
UPDATE `security_functions` SET `_view`='Classes.index|Classes.view', `_edit`=NULL, `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='3014';
UPDATE `security_functions` SET `_view`='StudentSections.index', `_edit`=NULL, `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='7011';
UPDATE `security_functions` SET `_view`='StaffSections.index', `_edit`=NULL, `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='7022';
UPDATE `security_functions` SET `_view`='StaffClasses.index', `_edit`=NULL, `_add`=NULL, `_delete`=NULL, `_execute`=NULL WHERE `id`='7023';


-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-1694';

-- 
-- END POCOR-1694
-- 


-- POCOR-2675
ALTER TABLE `institution_positions` DROP `is_homeroom`;
ALTER TABLE `security_roles` DROP `code`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-2675';


-- POCOR-2749
-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2749';


-- 3.4.17
UPDATE config_items SET value = '3.4.17' WHERE code = 'db_version';
