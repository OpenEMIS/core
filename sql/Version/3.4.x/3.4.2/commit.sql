-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-1961', NOW());

ALTER TABLE `institutions` ADD `institution_network_connectivity_id` INT NOT NULL AFTER `institution_gender_id`;
ALTER TABLE `institutions` ADD INDEX(`institution_network_connectivity_id`);

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'Institutions', 'institution_network_connectivity_id', 'Institutions', 'Network Connectivity', NULL, NULL, '1', 1, NOW());


SET @fieldOptionOrder := 0;
SELECT field_options.order INTO @fieldOptionOrder FROM field_options WHERE code = 'Genders';
UPDATE field_options SET field_options.order = field_options.order+1 WHERE field_options.order > @fieldOptionOrder;
INSERT INTO `field_options` (`id`, `plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `created_user_id`, `created`) VALUES (NULL, 'Institution', 'NetworkConnectivities', 'Network Connectivity', 'Institution', '{"model":"Institution.NetworkConnectivities"}', @fieldOptionOrder+1, 1, 1, NOW());



--
-- Creating table 'institution_network_connectivities'
--
CREATE TABLE `institution_network_connectivities` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `editable` int(1) NOT NULL DEFAULT '1',
  `default` int(1) NOT NULL DEFAULT '0',
  `international_code` varchar(50) DEFAULT NULL,
  `national_code` varchar(50) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `institution_network_connectivities`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `institution_network_connectivities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


INSERT INTO institution_network_connectivities (
`name`, 
`order`, 
`visible`, 
`editable`, 
`default`, 
`international_code`, 
`national_code`, 
`created_user_id`, 
`created`
) VALUES 
(
	'None',
	0, 
	1, 
	1, 
	1, 
	NULL, 
	NULL,
	1,
	NOW()
),
(
	'Internet-assisted Instruction',
	0, 
	1, 
	1, 
	0, 
	NULL, 
	NULL,
	1,
	NOW()
),
(
	'Fixed Broadband Internet',
	0, 
	1, 
	1, 
	0, 
	NULL, 
	NULL,
	1,
	NOW()
),
(
	'Wireless broadband Internet',
	0, 
	1, 
	1, 
	0, 
	NULL, 
	NULL,
	1,
	NOW()
),
(
	'Narrowband Internet',
	0, 
	1, 
	1, 
	0, 
	NULL, 
	NULL,
	1,
	NOW()
);

UPDATE institution_network_connectivities SET institution_network_connectivities.order = institution_network_connectivities.id;






-- PHPOE-2319
INSERT INTO `db_patches` VALUES ('PHPOE-2319', NOW());

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES 
(1034, 'Import Institutions', 'Institutions', 'Institutions', 'General', '8', NULL, NULL, NULL, NULL, 'ImportInstitutions.add|ImportInstitutions.results|ImportInstitutions.template', '1034', '1', '1', NOW()),
(1035, 'Import Students', 'Institutions', 'Institutions', 'Students', '1012', NULL, NULL, NULL, NULL, 'ImportStudents.add|ImportStudents.results|ImportStudents.template', '1035', '1', '1', NOW()),
(1036, 'Import Student Attendances', 'Institutions', 'Institutions', 'Students', '1012', NULL, NULL, NULL, NULL, 'ImportStudentAttendances.add|ImportStudentAttendances.results|ImportStudentAttendances.template', '1036', '1', '1', NOW()),
(1037, 'Import Staff Attendances', 'Institutions', 'Institutions', 'Staff', '1016', NULL, NULL, NULL, NULL, 'ImportStaffAttendances.add|ImportStaffAttendances.results|ImportStaffAttendances.template', '1037', '1', '1', NOW()),
(7036, 'Import Users', 'Directories', 'Directory', 'General', '7000', NULL, NULL, NULL, NULL, 'ImportUsers.add|ImportUsers.results|ImportUsers.template', '7036', '1', '1', NOW())
;

--
-- PHPOE-2319 PATCH --
--
UPDATE `security_functions` SET `_execute`='ImportInstitutions.add|ImportInstitutions.template|ImportInstitutions.results|ImportInstitutions.downloadFailed' WHERE `id`=1034;
UPDATE `security_functions` SET `_execute`='ImportStudents.add|ImportStudents.template|ImportStudents.results|ImportStudents.downloadFailed' WHERE `id`=1035;
UPDATE `security_functions` SET `_execute`='ImportStudentAttendances.add|ImportStudentAttendances.template|ImportStudentAttendances.results|ImportStudentAttendances.downloadFailed' WHERE `id`=1036;
UPDATE `security_functions` SET `_execute`='ImportStaffAttendances.add|ImportStaffAttendances.template|ImportStaffAttendances.results|ImportStaffAttendances.downloadFailed' WHERE `id`=1037;
UPDATE `security_functions` SET `_execute`='ImportUsers.add|ImportUsers.template|ImportUsers.results|ImportUsers.downloadFailed' WHERE `id`=7036;
UPDATE `security_functions` SET `_execute`='ImportInstitutionSurveys.add|ImportInstitutionSurveys.template|ImportInstitutionSurveys.results|ImportInstitutionSurveys.downloadFailed' WHERE `id`=1024;
--
-- END PHPOE-2319 PATCH --
--


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

-- PHPOE-2366
INSERT INTO `db_patches` VALUES ('PHPOE-2366', NOW());

CREATE TABLE `z_2366_import_mapping` LIKE `import_mapping`;
INSERT INTO `z_2366_import_mapping` SELECT * FROM `import_mapping`;

ALTER TABLE `import_mapping` CHANGE `foreign_key` `foreign_key` INT(11) NULL DEFAULT '0' COMMENT '0: not foreign key, 1: field options, 2: direct table, 3: non-table list';

INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) 
values
('User.Users', 'openemis_no', '(Leave as blank for new entries)', 1, 0, NULL, NULL, NULL),
('User.Users', 'first_name', NULL, 2, 0, NULL, NULL, NULL),
('User.Users', 'middle_name', NULL, 3, 0, NULL, NULL, NULL),
('User.Users', 'third_name', NULL, 4, 0, NULL, NULL, NULL),
('User.Users', 'last_name', NULL, 5, 0, NULL, NULL, NULL),
('User.Users', 'preferred_name', NULL, 6, 0, NULL, NULL, NULL),
('User.Users', 'gender_id', 'Code (M/F)', 7, 2, 'User', 'Genders', 'code'),
('User.Users', 'date_of_birth', NULL, 8, 0, NULL, NULL, NULL),
('User.Users', 'address', NULL, 9, 0, NULL, NULL, NULL),
('User.Users', 'postal_code', NULL, 10, 0, NULL, NULL, NULL),
('User.Users', 'address_area_id', 'Code', 11, 2, 'Area', 'AreaAdministratives', 'code'),
('User.Users', 'birthplace_area_id', 'Code', 12, 2, 'Area', 'AreaAdministratives', 'code'),
('User.Users', 'account_type', 'Code', 13, 3, NULL, 'AccountTypes', 'code');

UPDATE config_items SET value = '3.4.2' WHERE code = 'db_version';
