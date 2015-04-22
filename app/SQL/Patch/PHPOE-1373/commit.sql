UPDATE `navigations` SET `pattern` = 'index$|advanced|InstitutionSiteStudent.index|import' WHERE `module` LIKE 'Student' AND `title` LIKE 'List of Students';
UPDATE `security_functions` SET `_add` = '_view:import|importTemplate|add', `_view` = 'index|view|InstitutionSiteStudent|InstitutionSiteStudent.index' 
WHERE `module` LIKE 'Students' AND `category` LIKE 'General' AND `name` LIKE 'Student';

UPDATE `navigations` SET `pattern` = 'index$|advanced|InstitutionSiteStaff.index|import' WHERE `module` LIKE 'Staff' AND `title` LIKE 'List of Staff';
UPDATE `security_functions` SET `_add` = '_view:import|importTemplate|add', `_view` = 'index|view|InstitutionSiteStaff|InstitutionSiteStaff.index' 
WHERE `module` LIKE 'Staff' AND `category` LIKE 'General' AND `name` LIKE 'Staff';

ALTER TABLE `genders` ADD `code` VARCHAR(10) NOT NULL AFTER `name`;
UPDATE `genders` SET `code` = 'M' WHERE `name` LIKE 'Male';
UPDATE `genders` SET `code` = 'F' WHERE `name` LIKE 'Female';

INSERT INTO `import_mapping` (`id`, `model`, `column_name`, `is_code`, `order`, `foreigh_key`, `lookup_model`, `lookup_column`) VALUES
(NULL, 'Student', 'openemis_no', 0, 1, 0, NULL, NULL),
(NULL, 'Student', 'first_name', 0, 2, 0, NULL, NULL),
(NULL, 'Student', 'middle_name', 0, 3, 0, NULL, NULL),
(NULL, 'Student', 'third_name', 0, 4, 0, NULL, NULL),
(NULL, 'Student', 'last_name', 0, 5, 0, NULL, NULL),
(NULL, 'Student', 'preferred_name', 0, 6, 0, NULL, NULL),
(NULL, 'Student', 'gender_id', 1, 7, 2, 'Gender', 'code'),
(NULL, 'Student', 'date_of_birth', 0, 8, 0, NULL, NULL),
(NULL, 'Student', 'address', 0, 9, 0, NULL, NULL),
(NULL, 'Student', 'postal_code', 0, 10, 0, NULL, NULL),
(NULL, 'Student', 'address_area_id', 1, 11, 2, 'Area', 'code'),
(NULL, 'Student', 'birthplace_area_id', 1, 12, 2, 'Area', 'code'),
(NULL, 'Staff', 'openemis_no', 0, 1, 0, NULL, NULL),
(NULL, 'Staff', 'first_name', 0, 2, 0, NULL, NULL),
(NULL, 'Staff', 'middle_name', 0, 3, 0, NULL, NULL),
(NULL, 'Staff', 'third_name', 0, 4, 0, NULL, NULL),
(NULL, 'Staff', 'last_name', 0, 5, 0, NULL, NULL),
(NULL, 'Staff', 'preferred_name', 0, 6, 0, NULL, NULL),
(NULL, 'Staff', 'gender_id', 1, 7, 2, 'Gender', 'code'),
(NULL, 'Staff', 'date_of_birth', 0, 8, 0, NULL, NULL),
(NULL, 'Staff', 'address', 0, 9, 0, NULL, NULL),
(NULL, 'Staff', 'postal_code', 0, 10, 0, NULL, NULL),
(NULL, 'Staff', 'address_area_id', 1, 11, 2, 'Area', 'code'),
(NULL, 'Staff', 'birthplace_area_id', 1, 12, 2, 'Area', 'code');

ALTER TABLE `import_mapping` CHANGE `foreigh_key` `foreign_key` INT(11) NULL DEFAULT '0' COMMENT '0: not foreign key, 1: normal foreign key, 2: heavy load foreign key';