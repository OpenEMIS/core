-- PHPOE-2359
INSERT INTO `db_patches` VALUES ('PHPOE-2359', NOW());

CREATE TABLE `z_2359_import_mapping` LIKE `import_mapping`;
INSERT INTO `z_2359_import_mapping` SELECT * FROM `import_mapping`;

UPDATE `import_mapping` set `model`=concat('Institution.', `model`) where `model`='Institutions';
UPDATE `import_mapping` set `model`=concat('Student.', `model`) where `model`='Students';
UPDATE `import_mapping` set `model`=concat('Staff.', `model`) where `model`='Staff';
UPDATE `import_mapping` set `model`=concat('Institution.', `model`) where `model`='StaffAbsences';
UPDATE `import_mapping` set `model`=concat('Institution.', `model`) where `model`='InstitutionStudentAbsences';
UPDATE `import_mapping` set `model`=concat('Institution.', `model`) where `model`='InstitutionSurveys';


INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) 
values
('Institution.Students', 'academic_period_id', 	'Code', '1', '2', 'AcademicPeriod', 'AcademicPeriods', 'code'),
('Institution.Students', 'education_grade_id', 	'Code', '2', '2', 'Education', 'EducationGrades', 'code'),
('Institution.Students', 'start_date', 			'', '3', '0', NULL, NULL, NULL),
('Institution.Students', 'student_id', 		'OpenEMIS ID', '4', '2', 'Student', 'Students', 'openemis_no')
;

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `created_user_id`, `created`) VALUES
(uuid(), 'Imports', 'institution_id', 'Imports', 'Institution', NULL, NULL, 1, 0, NOW())
;
