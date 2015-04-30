-- PHPOE-1338

UPDATE `navigations` SET `pattern` = 'index$|advanced|import' WHERE `module` LIKE 'Institution' AND `title` LIKE 'List of Institutions';
UPDATE `security_functions` SET `_add` = '_view:import|importTemplate|add' WHERE `module` LIKE 'Institutions' AND `category` LIKE 'General' AND `name` LIKE 'Institution';

--
-- Table structure for table `import_mapping`
--

CREATE TABLE `import_mapping` (
`id` int(11) NOT NULL,
  `model` varchar(50) NOT NULL,
  `column_name` varchar(30) NOT NULL,
  `is_code` int(11) NOT NULL DEFAULT '0',
  `order` int(11) DEFAULT '0',
  `foreigh_key` int(11) DEFAULT '0' COMMENT '0: not foreign key, 1: normal foreign key, 2: heavy load foreign key',
  `lookup_model` varchar(50) DEFAULT NULL,
  `lookup_column` varchar(50) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `import_mapping`
--

INSERT INTO `import_mapping` (`id`, `model`, `column_name`, `is_code`, `order`, `foreigh_key`, `lookup_model`, `lookup_column`) VALUES
(1, 'InstitutionSite', 'name', 0, 1, 0, NULL, NULL),
(2, 'InstitutionSite', 'alternative_name', 0, 2, 0, NULL, NULL),
(3, 'InstitutionSite', 'code', 0, 3, 0, NULL, NULL),
(4, 'InstitutionSite', 'address', 0, 4, 0, NULL, NULL),
(5, 'InstitutionSite', 'postal_code', 0, 5, 0, NULL, NULL),
(6, 'InstitutionSite', 'contact_person', 0, 6, 0, NULL, NULL),
(7, 'InstitutionSite', 'telephone', 0, 7, 0, NULL, NULL),
(8, 'InstitutionSite', 'fax', 0, 8, 0, NULL, NULL),
(9, 'InstitutionSite', 'email', 0, 9, 0, NULL, NULL),
(10, 'InstitutionSite', 'date_opened', 0, 11, 0, NULL, NULL),
(11, 'InstitutionSite', 'year_opened', 0, 12, 0, NULL, NULL),
(12, 'InstitutionSite', 'date_closed', 0, 13, 0, NULL, NULL),
(13, 'InstitutionSite', 'year_closed', 0, 14, 0, NULL, NULL),
(14, 'InstitutionSite', 'longitude', 0, 15, 0, NULL, NULL),
(15, 'InstitutionSite', 'website', 0, 10, 0, NULL, NULL),
(16, 'InstitutionSite', 'latitude', 0, 16, 0, NULL, NULL),
(17, 'InstitutionSite', 'area_id', 1, 17, 2, 'Area', 'code'),
(18, 'InstitutionSite', 'area_administrative_id', 1, 18, 2, 'AreaAdministrative', 'code'),
(19, 'InstitutionSite', 'institution_site_locality_id', 1, 19, 1, 'InstitutionSiteLocality', 'national_code'),
(20, 'InstitutionSite', 'institution_site_type_id', 1, 20, 1, 'InstitutionSiteType', 'national_code'),
(21, 'InstitutionSite', 'institution_site_ownership_id', 1, 21, 1, 'InstitutionSiteOwnership', 'national_code'),
(22, 'InstitutionSite', 'institution_site_status_id', 1, 22, 1, 'InstitutionSiteStatus', 'national_code'),
(23, 'InstitutionSite', 'institution_site_sector_id', 1, 23, 1, 'InstitutionSiteSector', 'national_code'),
(24, 'InstitutionSite', 'institution_site_provider_id', 1, 24, 1, 'InstitutionSiteProvider', 'national_code'),
(25, 'InstitutionSite', 'institution_site_gender_id', 1, 25, 1, 'InstitutionSiteGender', 'national_code');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `import_mapping`
--
ALTER TABLE `import_mapping`
 ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `import_mapping`
--
ALTER TABLE `import_mapping`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=26;

-- PHPOE-1373

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
(NULL, 'Student', 'openemis_no', '(Leave as blank for new entries)', 1, 0, NULL, NULL),
(NULL, 'Student', 'first_name', NULL, 2, 0, NULL, NULL),
(NULL, 'Student', 'middle_name', NULL, 3, 0, NULL, NULL),
(NULL, 'Student', 'third_name', NULL, 4, 0, NULL, NULL),
(NULL, 'Student', 'last_name', NULL, 5, 0, NULL, NULL),
(NULL, 'Student', 'preferred_name', NULL, 6, 0, NULL, NULL),
(NULL, 'Student', 'gender_id', 'Code (M/F)', 7, 2, 'Gender', 'code'),
(NULL, 'Student', 'date_of_birth', NULL, 8, 0, NULL, NULL),
(NULL, 'Student', 'address', NULL, 9, 0, NULL, NULL),
(NULL, 'Student', 'postal_code', NULL, 10, 0, NULL, NULL),
(NULL, 'Student', 'address_area_id', 'Code', 11, 2, 'AreaAdministrative', 'code'),
(NULL, 'Student', 'birthplace_area_id', 'Code', 12, 2, 'AreaAdministrative', 'code'),
(NULL, 'Staff', 'openemis_no', '(Leave as blank for new entries)', 1, 0, NULL, NULL),
(NULL, 'Staff', 'first_name', NULL, 2, 0, NULL, NULL),
(NULL, 'Staff', 'middle_name', NULL, 3, 0, NULL, NULL),
(NULL, 'Staff', 'third_name', NULL, 4, 0, NULL, NULL),
(NULL, 'Staff', 'last_name', NULL, 5, 0, NULL, NULL),
(NULL, 'Staff', 'preferred_name', NULL, 6, 0, NULL, NULL),
(NULL, 'Staff', 'gender_id', 'Code (M/F)', 7, 2, 'Gender', 'code'),
(NULL, 'Staff', 'date_of_birth', NULL, 8, 0, NULL, NULL),
(NULL, 'Staff', 'address', NULL, 9, 0, NULL, NULL),
(NULL, 'Staff', 'postal_code', NULL, 10, 0, NULL, NULL),
(NULL, 'Staff', 'address_area_id', 'Code', 11, 2, 'AreaAdministrative', 'code'),
(NULL, 'Staff', 'birthplace_area_id', 'Code', 12, 2, 'AreaAdministrative', 'code');

UPDATE `import_mapping` SET `description` = NULL WHERE `model` LIKE 'InstitutionSite';
UPDATE `import_mapping` SET `description` = 'Code' WHERE `model` LIKE 'InstitutionSite' AND (`foreign_key` = 1 OR `foreign_key` = 2);

-- PHPOE-1272

ALTER TABLE `countries` ADD `identity_type_id` INT NULL AFTER `name`;

ALTER TABLE `user_identities` CHANGE `issue_date` `issue_date` DATE NULL;
ALTER TABLE `user_identities` CHANGE `expiry_date` `expiry_date` DATE NULL;
ALTER TABLE `user_identities` CHANGE `issue_location` `issue_location` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;

-- Removing is_wizard
UPDATE navigations SET is_wizard = 0 WHERE action = 'StudentNationality';
UPDATE navigations SET is_wizard = 0 WHERE action = 'StudentIdentity';
UPDATE navigations SET is_wizard = 0 WHERE action = 'StaffNationality';
UPDATE navigations SET is_wizard = 0 WHERE action = 'StaffIdentity';

-- PHPOE-1386

UPDATE config_items SET name = "StaffIdentity" WHERE name = "staff_identities";
UPDATE config_items SET name = "StaffNationality" WHERE name = "staff_nationalities";
UPDATE config_items SET name = "StaffContact" WHERE name = "staff_contacts";
UPDATE config_items SET name = "StaffSpecialNeed" WHERE name = "staff_specialNeed";

-- removing 
DELETE FROM config_items WHERE name = 'staff_membership';
DELETE FROM config_items WHERE name = 'staff_license';
DELETE FROM config_items WHERE name = 'staff_languages';
DELETE FROM config_items WHERE name = 'staff_comments';
DELETE FROM config_items WHERE name = 'staff_bankAccounts';
DELETE FROM config_items WHERE name = 'staff_award';
DELETE FROM config_items WHERE name = 'staff_attachments';

-- PHPOE-1390
UPDATE config_items SET name = "StudentIdentity" WHERE name = "student_identities";
UPDATE config_items SET name = "StudentNationality" WHERE name = "student_nationalities";
UPDATE config_items SET name = "StudentContact" WHERE name = "student_contacts";
UPDATE config_items SET name = "StudentSpecialNeed" WHERE name = "student_specialNeed";


-- removing 
DELETE FROM config_items WHERE name = 'student_languages';
DELETE FROM config_items WHERE name = 'student_comments';
DELETE FROM config_items WHERE name = 'student_bankAccounts';
DELETE FROM config_items WHERE name = 'student_award';
DELETE FROM config_items WHERE name = 'student_attachments';

-- renaming
UPDATE config_items SET type = "Add New Staff" WHERE type = "Wizard - Add New Staff";
UPDATE config_items SET type = "Add New Student" WHERE type = "Wizard - Add New Student";

UPDATE navigations SET title = 'Add Student' WHERE navigations.controller = 'Students' AND navigations.title = 'Add new Student';
UPDATE navigations SET title = 'Add Student' WHERE navigations.controller = 'Students' AND navigations.title = 'Add existing Student';

-- making sure left nav is correct
UPDATE navigations SET visible = '1' WHERE navigations.controller = 'Students' AND navigations.title = 'Add Student';

