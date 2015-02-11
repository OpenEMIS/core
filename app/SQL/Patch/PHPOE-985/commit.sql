-- Move the Memberships and Licenses menu items in the Staff module from General
SELECT MIN(navigations.order) into @prevMembershipOrder FROM navigations WHERE title = 'Memberships' OR title = 'Licenses';
UPDATE navigations SET navigations.order = navigations.order - 2 WHERE navigations.order >= @prevMembershipOrder;
SELECT MIN(navigations.order) into @prevSalaryOrder FROM navigations WHERE title = 'Salary';
UPDATE navigations SET navigations.order = navigations.order + 2 WHERE navigations.order > @prevSalaryOrder;
UPDATE navigations SET navigations.order = @prevSalaryOrder + 1 WHERE title = 'Memberships';
UPDATE navigations SET navigations.order = @prevSalaryOrder + 2 WHERE title = 'Licenses';
UPDATE navigations SET header = 'Details' WHERE title = 'Memberships' OR title = 'Licenses';

-- security_users backup
CREATE TABLE IF NOT EXISTS 985_security_users LIKE security_users;
INSERT 985_security_users SELECT * FROM security_users WHERE NOT EXISTS (SELECT * FROM 985_security_users);

-- security_users user edit
ALTER TABLE `security_users` ADD `middle_name` VARCHAR(100) NULL DEFAULT NULL AFTER `first_name`;
ALTER TABLE `security_users` ADD `third_name` VARCHAR(100) NULL DEFAULT NULL AFTER `middle_name`;
ALTER TABLE `security_users` ADD `preferred_name` VARCHAR(100) NULL DEFAULT NULL AFTER `last_name`;
ALTER TABLE `security_users` ADD `address` text AFTER `preferred_name`;
ALTER TABLE `security_users` ADD `postal_code` varchar(20) NULL DEFAULT NULL AFTER `address`;
ALTER TABLE `security_users` ADD `address_area_id` int(11) DEFAULT '0' AFTER `postal_code`;
ALTER TABLE `security_users` ADD `birthplace_area_id` int(11) DEFAULT '0' AFTER `address_area_id`;
ALTER TABLE `security_users` ADD `photo_name` varchar(250) DEFAULT '' AFTER `last_login`;
ALTER TABLE `security_users` ADD `photo_content` longblob AFTER `photo_name`;

ALTER TABLE `security_users` CHANGE `username` `username` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;
ALTER TABLE `security_users` CHANGE `password` `password` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;
ALTER TABLE `security_users` CHANGE `identification_no` `openemis_no` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

-- navigations for controller2
UPDATE navigations SET action = 'StudentContact', pattern = 'StudentContact' WHERE title = 'Contacts' AND header = 'GENERAL' AND controller = 'Students';
UPDATE navigations SET action = 'StudentIdentity', pattern = 'StudentIdentity' WHERE title = 'Identities' AND header = 'GENERAL' AND controller = 'Students';
UPDATE navigations SET action = 'StudentNationality', pattern = 'StudentNationality' WHERE title = 'Nationalities' AND header = 'GENERAL' AND controller = 'Students';
UPDATE navigations SET action = 'StudentLanguage', pattern = 'StudentLanguage' WHERE title = 'Languages' AND header = 'GENERAL' AND controller = 'Students';
UPDATE navigations SET action = 'StudentComment', pattern = 'StudentComment' WHERE title = 'Comments' AND header = 'GENERAL' AND controller = 'Students';
UPDATE navigations SET action = 'StudentSpecialNeed', pattern = 'StudentSpecialNeed' WHERE title = 'Special Needs' AND header = 'GENERAL' AND controller = 'Students';
UPDATE navigations SET action = 'StudentAward', pattern = 'StudentAward' WHERE title = 'Awards' AND header = 'GENERAL' AND controller = 'Students';
UPDATE navigations SET action = 'StaffContact', pattern = 'StaffContact' WHERE title = 'Contacts' AND header = 'GENERAL' AND controller = 'Staff';
UPDATE navigations SET action = 'StaffIdentity', pattern = 'StaffIdentity' WHERE title = 'Identities' AND header = 'GENERAL' AND controller = 'Staff';
UPDATE navigations SET action = 'StaffNationality', pattern = 'StaffNationality' WHERE title = 'Nationalities' AND header = 'GENERAL' AND controller = 'Staff';
UPDATE navigations SET action = 'StaffLanguage', pattern = 'StaffLanguage' WHERE title = 'Languages' AND header = 'GENERAL' AND controller = 'Staff';
UPDATE navigations SET action = 'StaffComment', pattern = 'StaffComment' WHERE title = 'Comments' AND header = 'GENERAL' AND controller = 'Staff';
UPDATE navigations SET action = 'StaffSpecialNeed', pattern = 'StaffSpecialNeed' WHERE title = 'Special Needs' AND header = 'GENERAL' AND controller = 'Staff';
UPDATE navigations SET action = 'StaffAward', pattern = 'StaffAward' WHERE title = 'Awards' AND header = 'GENERAL' AND controller = 'Staff';

-- ~~comment related sql
ALTER TABLE `student_comments` CHANGE `comment_date` `comment_date` DATE NOT NULL;
ALTER TABLE `staff_comments` CHANGE `comment_date` `comment_date` DATE NOT NULL;

--Security functions must be handled to converted modules
UPDATE security_functions SET _view = 'StudentContact|StudentContact.index|StudentContact.view', _edit = '_view:StudentContact.edit', _add = '_view:StudentContact.add', _delete = '_view:StudentContact.remove', _execute = '_view:StudentContact.excel' WHERE controller = 'Students' AND module = 'Students' AND category = 'Details' AND name = 'Contacts';
UPDATE security_functions SET _view = 'StudentIdentity|StudentIdentity.index|StudentIdentity.view', _edit = '_view:StudentIdentity.edit', _add = '_view:StudentIdentity.add', _delete = '_view:StudentIdentity.remove', _execute = '_view:StudentIdentity.excel' WHERE controller = 'Students' AND module = 'Students' AND category = 'Details' AND name = 'Identities';
UPDATE security_functions SET _view = 'StudentNationality|StudentNationality.index|StudentNationality.view', _edit = '_view:StudentNationality.edit', _add = '_view:StudentNationality.add', _delete = '_view:StudentNationality.remove', _execute = '_view:StudentNationality.excel' WHERE controller = 'Students' AND module = 'Students' AND category = 'Details' AND name = 'Nationalities';
UPDATE security_functions SET _view = 'StudentLanguage|StudentLanguage.index|StudentLanguage.view', _edit = '_view:StudentLanguage.edit', _add = '_view:StudentLanguage.add', _delete = '_view:StudentLanguage.remove', _execute = '_view:StudentLanguage.excel' WHERE controller = 'Students' AND module = 'Students' AND category = 'Details' AND name = 'Languages';
UPDATE security_functions SET _view = 'StudentComment|StudentComment.index|StudentComment.view', _edit = '_view:StudentComment.edit', _add = '_view:StudentComment.add', _delete = '_view:StudentComment.remove', _execute = '_view:StudentComment.excel' WHERE controller = 'Students' AND module = 'Students' AND category = 'Details' AND name = 'Comments';
UPDATE security_functions SET _view = 'StudentSpecialNeed|StudentSpecialNeed.index|StudentSpecialNeed.view', _edit = '_view:StudentSpecialNeed.edit', _add = '_view:StudentSpecialNeed.add', _delete = '_view:StudentSpecialNeed.remove', _execute = '_view:StudentSpecialNeed.excel' WHERE controller = 'Students' AND module = 'Students' AND category = 'Details' AND name = 'Needs';
UPDATE security_functions SET _view = 'StudentAward|StudentAward.index|StudentAward.view', _edit = '_view:StudentAward.edit', _add = '_view:StudentAward.add', _delete = '_view:StudentAward.remove', _execute = '_view:StudentAward.excel' WHERE controller = 'Students' AND module = 'Students' AND category = 'Details' AND name = 'Awards';
UPDATE security_functions SET _view = 'StaffContact|StaffContact.index|StaffContact.view', _edit = '_view:StaffContact.edit', _add = '_view:StaffContact.add', _delete = '_view:StaffContact.remove', _execute = '_view:StaffContact.excel' WHERE controller = 'Students' AND module = 'Students' AND category = 'Details' AND name = 'Contacts';
UPDATE security_functions SET _view = 'StaffIdentity|StaffIdentity.index|StaffIdentity.view', _edit = '_view:StaffIdentity.edit', _add = '_view:StaffIdentity.add', _delete = '_view:StaffIdentity.remove', _execute = '_view:StaffIdentity.excel' WHERE controller = 'Students' AND module = 'Students' AND category = 'Details' AND name = 'Identities';
UPDATE security_functions SET _view = 'StaffNationality|StaffNationality.index|StaffNationality.view', _edit = '_view:StaffNationality.edit', _add = '_view:StaffNationality.add', _delete = '_view:StaffNationality.remove', _execute = '_view:StaffNationality.excel' WHERE controller = 'Students' AND module = 'Students' AND category = 'Details' AND name = 'Nationalities';
UPDATE security_functions SET _view = 'StaffLanguage|StaffLanguage.index|StaffLanguage.view', _edit = '_view:StaffLanguage.edit', _add = '_view:StaffLanguage.add', _delete = '_view:StaffLanguage.remove', _execute = '_view:StaffLanguage.excel' WHERE controller = 'Students' AND module = 'Students' AND category = 'Details' AND name = 'Languages';
UPDATE security_functions SET _view = 'StaffComment|StaffComment.index|StaffComment.view', _edit = '_view:StaffComment.edit', _add = '_view:StaffComment.add', _delete = '_view:StaffComment.remove', _execute = '_view:StaffComment.excel' WHERE controller = 'Students' AND module = 'Students' AND category = 'Details' AND name = 'Comments';
UPDATE security_functions SET _view = 'StaffSpecialNeed|StaffSpecialNeed.index|StaffSpecialNeed.view', _edit = '_view:StaffSpecialNeed.edit', _add = '_view:StaffSpecialNeed.add', _delete = '_view:StaffSpecialNeed.remove', _execute = '_view:StaffSpecialNeed.excel' WHERE controller = 'Students' AND module = 'Students' AND category = 'Details' AND name = 'Needs';
UPDATE security_functions SET _view = 'StaffAward|StaffAward.index|StaffAward.view', _edit = '_view:StaffAward.edit', _add = '_view:StaffAward.add', _delete = '_view:StaffAward.remove', _execute = '_view:StaffAward.excel' WHERE controller = 'Students' AND module = 'Students' AND category = 'Details' AND name = 'Awards';

-- create new tables to combined tables
CREATE TABLE IF NOT EXISTS user_identities LIKE student_identities;
CREATE TABLE IF NOT EXISTS user_nationalities LIKE student_nationalities;
CREATE TABLE IF NOT EXISTS user_languages LIKE student_languages;
CREATE TABLE IF NOT EXISTS user_comments LIKE student_comments;
CREATE TABLE IF NOT EXISTS user_special_needs LIKE student_special_needs;
CREATE TABLE IF NOT EXISTS user_awards LIKE student_awards;
CREATE TABLE IF NOT EXISTS user_contacts LIKE student_contacts;

ALTER TABLE user_identities CHANGE `student_id` `security_user_id` INT(11) NOT NULL;
ALTER TABLE user_nationalities CHANGE `student_id` `security_user_id` INT(11) NOT NULL;
ALTER TABLE user_languages CHANGE `student_id` `security_user_id` INT(11) NOT NULL;
ALTER TABLE user_comments CHANGE `student_id` `security_user_id` INT(11) NOT NULL;
ALTER TABLE user_special_needs CHANGE `student_id` `security_user_id` INT(11) NOT NULL;
ALTER TABLE user_awards CHANGE `student_id` `security_user_id` INT(11) NOT NULL;
ALTER TABLE user_contacts CHANGE `student_id` `security_user_id` INT(11) NOT NULL;



