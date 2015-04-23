UPDATE `navigations` SET `pattern` = 'index$|advanced|InstitutionSiteStudent.index|import' WHERE `module` LIKE 'Student' AND `title` LIKE 'List of Students';
UPDATE `security_functions` SET `_add` = '_view:import|importTemplate|downloadFailed|add', `_view` = 'index|view|InstitutionSiteStudent|InstitutionSiteStudent.index' 
WHERE `module` LIKE 'Students' AND `category` LIKE 'General' AND `name` LIKE 'Student';

UPDATE `navigations` SET `pattern` = 'index$|advanced|InstitutionSiteStaff.index|import' WHERE `module` LIKE 'Staff' AND `title` LIKE 'List of Staff';
UPDATE `security_functions` SET `_add` = '_view:import|importTemplate|downloadFailed|add', `_view` = 'index|view|InstitutionSiteStaff|InstitutionSiteStaff.index' 
WHERE `module` LIKE 'Staff' AND `category` LIKE 'General' AND `name` LIKE 'Staff';

UPDATE `security_functions` SET `_add` = '_view:import|importTemplate|downloadFailed|add' WHERE `module` LIKE 'Institutions' AND `category` LIKE 'General' AND `name` LIKE 'Institution';

ALTER TABLE `genders` ADD `code` VARCHAR(10) NOT NULL AFTER `name`;
UPDATE `genders` SET `code` = 'M' WHERE `name` LIKE 'Male';
UPDATE `genders` SET `code` = 'F' WHERE `name` LIKE 'Female';

ALTER TABLE `import_mapping` CHANGE `is_code` `description` VARCHAR(50) NULL DEFAULT NULL;

ALTER TABLE `import_mapping` CHANGE `foreigh_key` `foreign_key` INT(11) NULL DEFAULT '0' COMMENT '0: not foreign key, 1: normal foreign key, 2: heavy load foreign key';

INSERT INTO `import_mapping` (`id`, `model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_model`, `lookup_column`) VALUES
(NULL, 'Student', 'openemis_no', '(Leave as null for new entries)', 1, 0, NULL, NULL),
(NULL, 'Student', 'first_name', NULL, 2, 0, NULL, NULL),
(NULL, 'Student', 'middle_name', NULL, 3, 0, NULL, NULL),
(NULL, 'Student', 'third_name', NULL, 4, 0, NULL, NULL),
(NULL, 'Student', 'last_name', NULL, 5, 0, NULL, NULL),
(NULL, 'Student', 'preferred_name', NULL, 6, 0, NULL, NULL),
(NULL, 'Student', 'gender_id', 'Code (M/F)', 7, 2, 'Gender', 'code'),
(NULL, 'Student', 'date_of_birth', NULL, 8, 0, NULL, NULL),
(NULL, 'Student', 'address', NULL, 9, 0, NULL, NULL),
(NULL, 'Student', 'postal_code', NULL, 10, 0, NULL, NULL),
(NULL, 'Student', 'address_area_id', 'Code', 11, 2, 'Area', 'code'),
(NULL, 'Student', 'birthplace_area_id', 'Code', 12, 2, 'Area', 'code'),
(NULL, 'Staff', 'openemis_no', '(Leave as null for new entries)', 1, 0, NULL, NULL),
(NULL, 'Staff', 'first_name', NULL, 2, 0, NULL, NULL),
(NULL, 'Staff', 'middle_name', NULL, 3, 0, NULL, NULL),
(NULL, 'Staff', 'third_name', NULL, 4, 0, NULL, NULL),
(NULL, 'Staff', 'last_name', NULL, 5, 0, NULL, NULL),
(NULL, 'Staff', 'preferred_name', NULL, 6, 0, NULL, NULL),
(NULL, 'Staff', 'gender_id', 'Code (M/F)', 7, 2, 'Gender', 'code'),
(NULL, 'Staff', 'date_of_birth', NULL, 8, 0, NULL, NULL),
(NULL, 'Staff', 'address', NULL, 9, 0, NULL, NULL),
(NULL, 'Staff', 'postal_code', NULL, 10, 0, NULL, NULL),
(NULL, 'Staff', 'address_area_id', 'Code', 11, 2, 'Area', 'code'),
(NULL, 'Staff', 'birthplace_area_id', 'Code', 12, 2, 'Area', 'code');