-- PHPOE-1338

UPDATE `navigations` SET `pattern` = 'index$|advanced' WHERE `module` LIKE 'Institution' AND `title` LIKE 'List of Institutions';
UPDATE `security_functions` SET `_add` = 'add' WHERE `module` LIKE 'Institutions' AND `category` LIKE 'General' AND `name` LIKE 'Institution';

DROP  TABLE IF EXISTS `import_mapping`;

-- PHPOE-1373

UPDATE `navigations` SET `pattern` = 'index$|advanced|InstitutionSiteStudent.index' WHERE `module` LIKE 'Student' AND `title` LIKE 'List of Students';
UPDATE `security_functions` SET `_add` = '_view:add', `_view` = 'view|InstitutionSiteStudent|InstitutionSiteStudent.index' 
WHERE `module` LIKE 'Students' AND `category` LIKE 'General' AND `name` LIKE 'Student';

UPDATE `navigations` SET `pattern` = 'index$|advanced|InstitutionSiteStaff.index' WHERE `module` LIKE 'Staff' AND `title` LIKE 'List of Staff';
UPDATE `security_functions` SET `_add` = '_view:add', `_view` = 'view|InstitutionSiteStaff|InstitutionSiteStaff.index' 
WHERE `module` LIKE 'Staff' AND `category` LIKE 'General' AND `name` LIKE 'Staff';

UPDATE `security_functions` SET `_add` = '_view:import|importTemplate|add' WHERE `module` LIKE 'Institutions' AND `category` LIKE 'General' AND `name` LIKE 'Institution';

ALTER TABLE `genders` DROP `code`;

DELETE FROM `import_mapping` WHERE `model` LIKE 'Student' OR `model` LIKE 'Staff';

-- PHPOE-1272

ALTER TABLE `countries` DROP `identity_type_id`;

ALTER TABLE `user_identities` CHANGE `issue_date` `issue_date` DATE NOT NULL;
ALTER TABLE `user_identities` CHANGE `expiry_date` `expiry_date` DATE NOT NULL;
ALTER TABLE `user_identities` CHANGE `issue_location` `issue_location` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

-- Removing is_wizard (rollback)
UPDATE navigations SET is_wizard = 1 WHERE action = 'StudentNationality';
UPDATE navigations SET is_wizard = 1 WHERE action = 'StudentIdentity';
UPDATE navigations SET is_wizard = 1 WHERE action = 'StaffNationality';
UPDATE navigations SET is_wizard = 1 WHERE action = 'StaffIdentity';

-- PHPOE-1386

UPDATE config_items SET name = "staff_identities" WHERE name = "StaffIdentity";
UPDATE config_items SET name = "staff_nationalities" WHERE name = "StaffNationality";
UPDATE config_items SET name = "staff_contacts" WHERE name = "StaffContact";
UPDATE config_items SET name = "staff_specialNeed" WHERE name = "StaffSpecialNeed";

-- PHPOE-1390

UPDATE config_items SET name = "student_identities" WHERE name = "StudentIdentity";
UPDATE config_items SET name = "student_nationalities" WHERE name = "StudentNationality";
UPDATE config_items SET name = "student_contacts" WHERE name = "StudentContact";
UPDATE config_items SET name = "student_specialNeed" WHERE name = "StudentSpecialNeed";

-- renaming
UPDATE config_items SET type = "Wizard - Add New Staff" WHERE type = "Add New Staff";
UPDATE config_items SET type = "Wizard - Add New Student" WHERE type = "Add New Student";

-- making sure left nav is correct
UPDATE navigations SET visible = '0' WHERE navigations.controller = 'Students' AND navigations.title = 'Add Student' AND action = 'add';