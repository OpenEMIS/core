-- Move the Memberships and Licenses menu items in the Staff module from General
SELECT MIN(navigations.order) into @prevMembershipOrder FROM navigations WHERE title = 'Memberships' OR title = 'Licenses';
UPDATE navigations SET navigations.order = navigations.order - 2 WHERE navigations.order >= @prevMembershipOrder;
SELECT MIN(navigations.order) into @prevSalaryOrder FROM navigations WHERE title = 'Salary';
UPDATE navigations SET navigations.order = navigations.order + 2 WHERE navigations.order > @prevSalaryOrder;
UPDATE navigations SET navigations.order = @prevSalaryOrder + 1 WHERE title = 'Memberships';
UPDATE navigations SET navigations.order = @prevSalaryOrder + 2 WHERE title = 'Licenses';
UPDATE navigations SET header = 'Details' WHERE title = 'Memberships' OR title = 'Licenses';

-- need to handle for security_functions too
SELECT MIN(security_functions.order) into @prevMembershipOrder FROM security_functions WHERE name = 'Memberships' OR name = 'Licenses';
UPDATE security_functions SET security_functions.order = security_functions.order - 2 WHERE security_functions.order >= @prevMembershipOrder;
SELECT MIN(security_functions.order) into @prevSalaryOrder FROM security_functions WHERE name = 'Salary';
UPDATE security_functions SET security_functions.order = security_functions.order + 2 WHERE security_functions.order > @prevSalaryOrder;
UPDATE security_functions SET security_functions.order = @prevSalaryOrder + 1 WHERE name = 'Memberships';
UPDATE security_functions SET security_functions.order = @prevSalaryOrder + 2 WHERE name = 'Licenses';
UPDATE security_functions SET category = 'Details' WHERE name = 'Memberships' OR name = 'Licenses';

-- security_users backup
CREATE TABLE IF NOT EXISTS z_985_security_users LIKE security_users;
INSERT z_985_security_users SELECT * FROM security_users WHERE NOT EXISTS (SELECT * FROM z_985_security_users);

-- security_users user edit
ALTER TABLE `security_users` ADD `middle_name` VARCHAR(100) NULL DEFAULT NULL AFTER `first_name`;
ALTER TABLE `security_users` ADD `third_name` VARCHAR(100) NULL DEFAULT NULL AFTER `middle_name`;
ALTER TABLE `security_users` ADD `preferred_name` VARCHAR(100) NULL DEFAULT NULL AFTER `last_name`;
ALTER TABLE `security_users` ADD `gender` char(1) NOT NULL COMMENT 'M for Male, F for Female' AFTER `preferred_name`;

ALTER TABLE `security_users` ADD `date_of_birth` date NOT NULL AFTER `gender`;
ALTER TABLE `security_users` ADD `date_of_death` date DEFAULT NULL AFTER `date_of_birth`;


  -- `date_of_birth` date NOT NULL,
  -- `date_of_death` date DEFAULT NULL,


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

-- Security functions must be handled to converted modules
UPDATE security_functions SET _view = 'StudentContact|StudentContact.index|StudentContact.view', _edit = '_view:StudentContact.edit', _add = '_view:StudentContact.add', _delete = '_view:StudentContact.remove', _execute = '_view:StudentContact.excel' WHERE controller = 'Students' AND module = 'Students' AND category = 'General' AND name = 'Contacts';
UPDATE security_functions SET _view = 'StudentIdentity|StudentIdentity.index|StudentIdentity.view', _edit = '_view:StudentIdentity.edit', _add = '_view:StudentIdentity.add', _delete = '_view:StudentIdentity.remove', _execute = '_view:StudentIdentity.excel' WHERE controller = 'Students' AND module = 'Students' AND category = 'General' AND name = 'Identities';
UPDATE security_functions SET _view = 'StudentNationality|StudentNationality.index|StudentNationality.view', _edit = '_view:StudentNationality.edit', _add = '_view:StudentNationality.add', _delete = '_view:StudentNationality.remove', _execute = '_view:StudentNationality.excel' WHERE controller = 'Students' AND module = 'Students' AND category = 'General' AND name = 'Nationalities';
UPDATE security_functions SET _view = 'StudentLanguage|StudentLanguage.index|StudentLanguage.view', _edit = '_view:StudentLanguage.edit', _add = '_view:StudentLanguage.add', _delete = '_view:StudentLanguage.remove', _execute = '_view:StudentLanguage.excel' WHERE controller = 'Students' AND module = 'Students' AND category = 'General' AND name = 'Languages';
UPDATE security_functions SET _view = 'StudentComment|StudentComment.index|StudentComment.view', _edit = '_view:StudentComment.edit', _add = '_view:StudentComment.add', _delete = '_view:StudentComment.remove', _execute = '_view:StudentComment.excel' WHERE controller = 'Students' AND module = 'Students' AND category = 'General' AND name = 'Comments';
UPDATE security_functions SET _view = 'StudentSpecialNeed|StudentSpecialNeed.index|StudentSpecialNeed.view', _edit = '_view:StudentSpecialNeed.edit', _add = '_view:StudentSpecialNeed.add', _delete = '_view:StudentSpecialNeed.remove', _execute = '_view:StudentSpecialNeed.excel' WHERE controller = 'Students' AND module = 'Students' AND category = 'General' AND name = 'Needs';
UPDATE security_functions SET _view = 'StudentAward|StudentAward.index|StudentAward.view', _edit = '_view:StudentAward.edit', _add = '_view:StudentAward.add', _delete = '_view:StudentAward.remove', _execute = '_view:StudentAward.excel' WHERE controller = 'Students' AND module = 'Students' AND category = 'General' AND name = 'Awards';
UPDATE security_functions SET _view = 'StaffContact|StaffContact.index|StaffContact.view', _edit = '_view:StaffContact.edit', _add = '_view:StaffContact.add', _delete = '_view:StaffContact.remove', _execute = '_view:StaffContact.excel' WHERE controller = 'Staff' AND module = 'Staff' AND category = 'General' AND name = 'Contacts';
UPDATE security_functions SET _view = 'StaffIdentity|StaffIdentity.index|StaffIdentity.view', _edit = '_view:StaffIdentity.edit', _add = '_view:StaffIdentity.add', _delete = '_view:StaffIdentity.remove', _execute = '_view:StaffIdentity.excel' WHERE controller = 'Staff' AND module = 'Staff' AND category = 'General' AND name = 'Identities';
UPDATE security_functions SET _view = 'StaffNationality|StaffNationality.index|StaffNationality.view', _edit = '_view:StaffNationality.edit', _add = '_view:StaffNationality.add', _delete = '_view:StaffNationality.remove', _execute = '_view:StaffNationality.excel' WHERE controller = 'Staff' AND module = 'Staff' AND category = 'General' AND name = 'Nationalities';
UPDATE security_functions SET _view = 'StaffLanguage|StaffLanguage.index|StaffLanguage.view', _edit = '_view:StaffLanguage.edit', _add = '_view:StaffLanguage.add', _delete = '_view:StaffLanguage.remove', _execute = '_view:StaffLanguage.excel' WHERE controller = 'Staff' AND module = 'Staff' AND category = 'General' AND name = 'Languages';
UPDATE security_functions SET _view = 'StaffComment|StaffComment.index|StaffComment.view', _edit = '_view:StaffComment.edit', _add = '_view:StaffComment.add', _delete = '_view:StaffComment.remove', _execute = '_view:StaffComment.excel' WHERE controller = 'Staff' AND module = 'Staff' AND category = 'General' AND name = 'Comments';
UPDATE security_functions SET _view = 'StaffSpecialNeed|StaffSpecialNeed.index|StaffSpecialNeed.view', _edit = '_view:StaffSpecialNeed.edit', _add = '_view:StaffSpecialNeed.add', _delete = '_view:StaffSpecialNeed.remove', _execute = '_view:StaffSpecialNeed.excel' WHERE controller = 'Staff' AND module = 'Staff' AND category = 'General' AND name = 'Needs';
UPDATE security_functions SET _view = 'StaffAward|StaffAward.index|StaffAward.view', _edit = '_view:StaffAward.edit', _add = '_view:StaffAward.add', _delete = '_view:StaffAward.remove', _execute = '_view:StaffAward.excel' WHERE controller = 'Staff' AND module = 'Staff' AND category = 'General' AND name = 'Awards';


-- backup student and staff tables
CREATE TABLE IF NOT EXISTS z_985_students LIKE students;
INSERT z_985_students SELECT * FROM students WHERE NOT EXISTS (SELECT * FROM z_985_students);
CREATE TABLE IF NOT EXISTS z_985_staff LIKE staff;
INSERT z_985_staff SELECT * FROM staff WHERE NOT EXISTS (SELECT * FROM z_985_staff);

-- student and staff migration to security_users
ALTER TABLE `students` ADD `security_user_id` INT NULL DEFAULT NULL AFTER `photo_content`;
ALTER TABLE `staff` ADD `security_user_id` INT NULL DEFAULT NULL AFTER `photo_content`;

ALTER TABLE `security_users` ADD `z_985_student_id` INT NULL COMMENT '985 for migration' AFTER `id`, ADD UNIQUE (`z_985_student_id`) ;
ALTER TABLE `security_users` ADD `z_985_staff_id` INT NULL COMMENT '985 for migration' AFTER `id`, ADD UNIQUE (`z_985_staff_id`) ;

INSERT INTO security_users (z_985_student_id, openemis_no, first_name, middle_name, third_name, last_name, preferred_name, gender, address, postal_code, address_area_id, birthplace_area_id, photo_name, photo_content, modified_user_id, modified, created_user_id, created, date_of_birth, date_of_death ) SELECT id, identification_no, first_name, middle_name, third_name, last_name, preferred_name, gender, address, postal_code, address_area_id, birthplace_area_id, photo_name, photo_content, modified_user_id, modified, created_user_id, created, date_of_birth, date_of_death FROM students;

INSERT INTO security_users (z_985_staff_id, openemis_no, first_name, middle_name, third_name, last_name, preferred_name, gender, address, postal_code, address_area_id, birthplace_area_id, photo_name, photo_content, modified_user_id, modified, created_user_id, created, date_of_birth, date_of_death ) SELECT id, identification_no, first_name, middle_name, third_name, last_name, preferred_name, gender, address, postal_code, address_area_id, birthplace_area_id, photo_name, photo_content, modified_user_id, modified, created_user_id, created, date_of_birth, date_of_death FROM staff;


-- link up tables
UPDATE security_users INNER JOIN students ON security_users.z_985_student_id = students.id SET students.security_user_id = security_users.id WHERE security_users.z_985_student_id IS NOT null; 
-- SELECT security_users.id, students.id AS student_id, security_users.z_985_student_id, students.security_user_id FROM security_users INNER JOIN students on security_users.z_985_student_id = students.id WHERE security_users.z_985_student_id IS NOT null;

UPDATE security_users INNER JOIN staff ON security_users.z_985_staff_id = staff.id SET staff.security_user_id = security_users.id WHERE security_users.z_985_staff_id IS NOT null;
-- SELECT security_users.id, staff.id AS staff_id, security_users.z_985_staff_id, staff.security_user_id FROM security_users INNER JOIN staff on security_users.z_985_staff_id = staff.id WHERE security_users.z_985_staff_id IS NOT null;

-- remove unneeded fields from student and staff
ALTER TABLE `students` DROP identification_no;
ALTER TABLE `students` DROP first_name;
ALTER TABLE `students` DROP middle_name;
ALTER TABLE `students` DROP third_name;
ALTER TABLE `students` DROP last_name;
ALTER TABLE `students` DROP preferred_name;
ALTER TABLE `students` DROP gender;
ALTER TABLE `students` DROP address;
ALTER TABLE `students` DROP postal_code;
ALTER TABLE `students` DROP address_area_id;
ALTER TABLE `students` DROP birthplace_area_id;
ALTER TABLE `students` DROP photo_name;
ALTER TABLE `students` DROP photo_content;
ALTER TABLE `students` DROP date_of_birth;
ALTER TABLE `students` DROP date_of_death;

ALTER TABLE `staff` DROP identification_no;
ALTER TABLE `staff` DROP first_name;
ALTER TABLE `staff` DROP middle_name;
ALTER TABLE `staff` DROP third_name;
ALTER TABLE `staff` DROP last_name;
ALTER TABLE `staff` DROP preferred_name;
ALTER TABLE `staff` DROP gender;
ALTER TABLE `staff` DROP address;
ALTER TABLE `staff` DROP postal_code;
ALTER TABLE `staff` DROP address_area_id;
ALTER TABLE `staff` DROP birthplace_area_id;
ALTER TABLE `staff` DROP photo_name;
ALTER TABLE `staff` DROP photo_content;
ALTER TABLE `staff` DROP date_of_birth;
ALTER TABLE `staff` DROP date_of_death;

-- create new tables to combined detail tables
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

-- migration of new detail tables
-- SELECT * FROM student_identities INNER JOIN students ON student_identities.student_id = students.id;


INSERT INTO user_identities (
identity_type_id, 
number, 
issue_date, 
expiry_date, 
issue_location, 
comments, 
security_user_id, 
modified_user_id, 
modified, 
created_user_id, 
created
   ) 
	SELECT 
		student_identities.identity_type_id,
		student_identities.number,
		student_identities.issue_date,
		student_identities.expiry_date,
		student_identities.issue_location,
		student_identities.comments,
		students.security_user_id,
		student_identities.modified_user_id,
		student_identities.modified,
		student_identities.created_user_id,
		student_identities.created
	FROM student_identities
		INNER JOIN students ON student_identities.student_id = students.id;

INSERT INTO user_identities (
identity_type_id, 
number, 
issue_date, 
expiry_date, 
issue_location, 
comments, 
security_user_id, 
modified_user_id, 
modified, 
created_user_id, 
created
   ) 
	SELECT 
		staff_identities.identity_type_id,
		staff_identities.number,
		staff_identities.issue_date,
		staff_identities.expiry_date,
		staff_identities.issue_location,
		staff_identities.comments,
		staff.security_user_id,
		staff_identities.modified_user_id,
		staff_identities.modified,
		staff_identities.created_user_id,
		staff_identities.created
	FROM staff_identities
		INNER JOIN staff ON staff_identities.staff_id = staff.id;

INSERT INTO user_nationalities (
country_id,
comments,
security_user_id,
modified_user_id,
modified,
created_user_id,
created
   ) 
	SELECT 
		student_nationalities.country_id,
		student_nationalities.comments,
		students.security_user_id,
		student_nationalities.modified_user_id,
		student_nationalities.modified,
		student_nationalities.created_user_id,
		student_nationalities.created
	FROM student_nationalities
		INNER JOIN students ON student_nationalities.student_id = students.id;

INSERT INTO user_nationalities (
country_id,
comments,
security_user_id,
modified_user_id,
modified,
created_user_id,
created
   ) 
	SELECT 
		staff_nationalities.country_id,
		staff_nationalities.comments,
		staff.security_user_id,
		staff_nationalities.modified_user_id,
		staff_nationalities.modified,
		staff_nationalities.created_user_id,
		staff_nationalities.created
	FROM staff_nationalities
		INNER JOIN staff ON staff_nationalities.staff_id = staff.id;

INSERT INTO user_languages (
evaluation_date,
language_id,
listening,
speaking,
reading,
writing,
security_user_id,
modified_user_id,
modified,
created_user_id,
created
   ) 
	SELECT 
		student_languages.evaluation_date,
		student_languages.language_id,
		student_languages.listening,
		student_languages.speaking,
		student_languages.reading,
		student_languages.writing,
		students.security_user_id,
		student_languages.modified_user_id,
		student_languages.modified,
		student_languages.created_user_id,
		student_languages.created
	FROM student_languages
		INNER JOIN students ON student_languages.student_id = students.id;

INSERT INTO user_languages (
evaluation_date,
language_id,
listening,
speaking,
reading,
writing,
security_user_id,
modified_user_id,
modified,
created_user_id,
created
   ) 
	SELECT 
		staff_languages.evaluation_date,
		staff_languages.language_id,
		staff_languages.listening,
		staff_languages.speaking,
		staff_languages.reading,
		staff_languages.writing,
		staff.security_user_id,
		staff_languages.modified_user_id,
		staff_languages.modified,
		staff_languages.created_user_id,
		staff_languages.created
	FROM staff_languages
		INNER JOIN staff ON staff_languages.staff_id = staff.id;

INSERT INTO user_comments (
		title,
		comment,
		comment_date,
		security_user_id,
		modified_user_id,
		modified,
		created_user_id,
		created
   ) 
	SELECT 
		student_comments.title,
		student_comments.comment,
		student_comments.comment_date,
		students.security_user_id,
		student_comments.modified_user_id,
		student_comments.modified,
		student_comments.created_user_id,
		student_comments.created
	FROM student_comments
		INNER JOIN students ON student_comments.student_id = students.id;

INSERT INTO user_comments (
		title,
		comment,
		comment_date,
		security_user_id,
		modified_user_id,
		modified,
		created_user_id,
		created
   ) 
	SELECT 
		staff_comments.title,
		staff_comments.comment,
		staff_comments.comment_date,
		staff.security_user_id,
		staff_comments.modified_user_id,
		staff_comments.modified,
		staff_comments.created_user_id,
		staff_comments.created
	FROM staff_comments
		INNER JOIN staff ON staff_comments.staff_id = staff.id;

INSERT INTO user_special_needs (
		special_need_type_id,
		special_need_date,
		comment,
		security_user_id,
		modified_user_id,
		modified,
		created_user_id,
		created
   ) 
	SELECT 
		student_special_needs.special_need_type_id,
		student_special_needs.special_need_date,
		student_special_needs.comment,
		students.security_user_id,
		student_special_needs.modified_user_id,
		student_special_needs.modified,
		student_special_needs.created_user_id,
		student_special_needs.created
	FROM student_special_needs
		INNER JOIN students ON student_special_needs.student_id = students.id;

INSERT INTO user_special_needs (
		special_need_type_id,
		special_need_date,
		comment,
		security_user_id,
		modified_user_id,
		modified,
		created_user_id,
		created
   ) 
	SELECT 
		staff_special_needs.special_need_type_id,
		staff_special_needs.special_need_date,
		staff_special_needs.comment,
		staff.security_user_id,
		staff_special_needs.modified_user_id,
		staff_special_needs.modified,
		staff_special_needs.created_user_id,
		staff_special_needs.created
	FROM staff_special_needs
		INNER JOIN staff ON staff_special_needs.staff_id = staff.id;


INSERT INTO user_awards (
		issue_date,
		award,
		issuer,
		comment,
		security_user_id,
		modified_user_id,
		modified,
		created_user_id,
		created
   ) 
	SELECT 
		student_awards.issue_date,
		student_awards.award,
		student_awards.issuer,
		student_awards.comment,
		students.security_user_id,
		student_awards.modified_user_id,
		student_awards.modified,
		student_awards.created_user_id,
		student_awards.created
	FROM student_awards
		INNER JOIN students ON student_awards.student_id = students.id;

INSERT INTO user_awards (
		issue_date,
		award,
		issuer,
		comment,
		security_user_id,
		modified_user_id,
		modified,
		created_user_id,
		created
   ) 
	SELECT 
		staff_awards.issue_date,
		staff_awards.award,
		staff_awards.issuer,
		staff_awards.comment,
		staff.security_user_id,
		staff_awards.modified_user_id,
		staff_awards.modified,
		staff_awards.created_user_id,
		staff_awards.created
	FROM staff_awards
		INNER JOIN staff ON staff_awards.staff_id = staff.id;

INSERT INTO user_contacts (
		contact_type_id,
		value,
		preferred,
		security_user_id,
		modified_user_id,
		modified,
		created_user_id,
		created
   ) 
	SELECT 
		student_contacts.contact_type_id,
		student_contacts.value,
		student_contacts.preferred,
		students.security_user_id,
		student_contacts.modified_user_id,
		student_contacts.modified,
		student_contacts.created_user_id,
		student_contacts.created
	FROM student_contacts
		INNER JOIN students ON student_contacts.student_id = students.id;

INSERT INTO user_contacts (
		contact_type_id,
		value,
		preferred,
		security_user_id,
		modified_user_id,
		modified,
		created_user_id,
		created
   ) 
	SELECT 
		staff_contacts.contact_type_id,
		staff_contacts.value,
		staff_contacts.preferred,
		staff.security_user_id,
		staff_contacts.modified_user_id,
		staff_contacts.modified,
		staff_contacts.created_user_id,
		staff_contacts.created
	FROM staff_contacts
		INNER JOIN staff ON staff_contacts.staff_id = staff.id;


-- create gender table
DROP TABLE IF EXISTS `genders`;
CREATE TABLE `genders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(10) NOT NULL,
  `order` int(11) NOT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

INSERT INTO `genders` (`id`, `name`, `order`, `created_user_id`, `created`) VALUES
(1, 'Male', 1, 1, NOW()),
(2, 'Female', 2, 1, NOW());

-- migration of gender
ALTER TABLE `security_users` ADD `gender_id` INT(1) NOT NULL AFTER `gender`;
UPDATE security_users SET gender_id = 1 WHERE gender = "M";
UPDATE security_users SET gender_id = 2 WHERE gender = "F";
ALTER TABLE `security_users` DROP `gender`;


-- need to pull from contact options
SELECT id INTO @email_contact_option_id FROM contact_options WHERE name = 'Email';
SELECT id INTO @telephone_contact_option_id FROM contact_options WHERE name = 'Phone';

UPDATE user_contacts SET preferred = 0 WHERE contact_type_id = @email_contact_option_id AND security_user_id IN (SELECT id FROM security_users WHERE email IS NOT NULL AND email <> '');
UPDATE user_contacts SET preferred = 0 WHERE contact_type_id = @telephone_contact_option_id AND security_user_id IN (SELECT id FROM security_users WHERE telephone IS NOT NULL AND telephone <> '');

INSERT INTO user_contacts (
preferred,
contact_type_id, 
value, 
security_user_id, 
modified_user_id,
modified,
created_user_id,
created
) SELECT 
1,
@email_contact_option_id,
email, 
id, 
modified_user_id,
modified,
created_user_id,
created 
FROM security_users WHERE email IS NOT NULL AND email <> '';


INSERT INTO user_contacts (
preferred,
contact_type_id, 
value, 
security_user_id, 
modified_user_id,
modified,
created_user_id,
created
) SELECT 
1,
@telephone_contact_option_id,
telephone, 
id, 
modified_user_id,
modified,
created_user_id,
created 
FROM security_users WHERE telephone IS NOT NULL AND telephone <> '';

-- removing of data that helps migration
ALTER TABLE `security_users` DROP `z_985_staff_id`;
ALTER TABLE `security_users` DROP `z_985_student_id`;
ALTER TABLE `security_users` DROP `telephone`;
ALTER TABLE `security_users` DROP `email`;


-- need to make menu appear for student/add
UPDATE navigations set pattern = replace(pattern , '^edit$|','^edit$|^add$|') WHERE module = 'Student' AND plugin = 'Students' AND controller = 'Students' AND header = 'General' AND title = 'Overview';
UPDATE navigations set pattern = replace(pattern , '^edit$|','^edit$|^add$|') WHERE module = 'Staff' AND plugin = 'Staff' AND controller = 'Staff' AND header = 'General' AND title = 'Overview';

-- Updating census gender_ids
SELECT id INTO @fieldGenderMale FROM field_option_values WHERE field_option_id IN (SELECT id FROM field_options WHERE code = 'Gender') AND name = 'Male';
SELECT id INTO @fieldGenderFemale FROM field_option_values WHERE field_option_id IN (SELECT id FROM field_options WHERE code = 'Gender') AND name = 'Female';
SELECT id INTO @newGenderMale FROM genders WHERE name = 'Male';
SELECT id INTO @newGenderFemale FROM genders WHERE name = 'Female';

UPDATE census_students SET gender_id = @newGenderMale WHERE gender_id = @fieldGenderMale;
UPDATE census_attendances SET gender_id = @newGenderMale WHERE gender_id = @fieldGenderMale;
UPDATE census_behaviours SET gender_id = @newGenderMale WHERE gender_id = @fieldGenderMale;
UPDATE census_graduates SET gender_id = @newGenderMale WHERE gender_id = @fieldGenderMale;
UPDATE census_teachers SET gender_id = @newGenderMale WHERE gender_id = @fieldGenderMale;
UPDATE census_teacher_fte SET gender_id = @newGenderMale WHERE gender_id = @fieldGenderMale;
UPDATE census_teacher_training SET gender_id = @newGenderMale WHERE gender_id = @fieldGenderMale;
UPDATE census_sanitations SET gender_id = @newGenderMale WHERE gender_id = @fieldGenderMale;

UPDATE census_students SET gender_id = @newGenderFemale WHERE gender_id = @fieldGenderFemale;
UPDATE census_attendances SET gender_id = @newGenderFemale WHERE gender_id = @fieldGenderFemale;
UPDATE census_behaviours SET gender_id = @newGenderFemale WHERE gender_id = @fieldGenderFemale;
UPDATE census_graduates SET gender_id = @newGenderFemale WHERE gender_id = @fieldGenderFemale;
UPDATE census_teachers SET gender_id = @newGenderFemale WHERE gender_id = @fieldGenderFemale;
UPDATE census_teacher_fte SET gender_id = @newGenderFemale WHERE gender_id = @fieldGenderFemale;
UPDATE census_teacher_training SET gender_id = @newGenderFemale WHERE gender_id = @fieldGenderFemale;
UPDATE census_sanitations SET gender_id = @newGenderFemale WHERE gender_id = @fieldGenderFemale;

-- for security / user to controlleractions2
UPDATE navigations SET action = 'SecurityUser', pattern = 'SecurityUser|SecurityUserAccess.view' WHERE action = 'users' AND module = 'Administration' AND controller = 'Security'AND title = 'Users';


-- update security for users
CREATE TABLE IF NOT EXISTS z_985_security_functions LIKE security_functions;
INSERT z_985_security_functions SELECT * FROM security_functions WHERE name = 'List of Users' AND controller = 'Security' AND module = 'Administration' AND NOT EXISTS (SELECT * FROM z_985_security_functions);
DELETE FROM security_functions WHERE name = 'List of Users' AND controller = 'Security' AND module = 'Administration';
UPDATE security_functions SET _view = 'SecurityUser|SecurityUser.index|SecurityUser.view', _edit = '_view:SecurityUser.edit|SecurityUserLogin.edit', _add = '_view:SecurityUser.add' WHERE name = 'Users' AND controller = 'Security' AND module = 'Administration';

SELECT security_functions.order INTO @securityUserOrder from security_functions WHERE name = 'Users' AND controller = 'Security' AND module = 'Administration';
UPDATE security_functions SET security_functions.order = security_functions.order - 1 WHERE security_functions.order >= @securityUserOrder;
-- select name, security_functions.order from security_functions where security_functions.order > 155 order by security_functions.order

UPDATE navigations SET action = 'SecurityUserLogin', pattern = 'SecurityUserLogin' WHERE action = 'password' AND module = 'Preferences' AND controller = 'Preferences' AND title = 'Password';

RENAME TABLE student_identities TO z_985_student_identities;
RENAME TABLE student_nationalities TO z_985_student_nationalities;
RENAME TABLE student_languages TO z_985_student_languages;
RENAME TABLE student_comments TO z_985_student_comments;
RENAME TABLE student_special_needs TO z_985_student_special_needs;
RENAME TABLE student_awards TO z_985_student_awards;
RENAME TABLE student_contacts TO z_985_student_contacts;
RENAME TABLE staff_identities TO z_985_staff_identities;
RENAME TABLE staff_nationalities TO z_985_staff_nationalities;
RENAME TABLE staff_languages TO z_985_staff_languages;
RENAME TABLE staff_comments TO z_985_staff_comments;
RENAME TABLE staff_special_needs TO z_985_staff_special_needs;
RENAME TABLE staff_awards TO z_985_staff_awards;
RENAME TABLE staff_contacts TO z_985_staff_contacts;
