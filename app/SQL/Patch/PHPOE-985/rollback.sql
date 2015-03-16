-- reversing: need to make menu appear for student/add 
UPDATE navigations set pattern = replace(pattern,'^edit$|^add$|' , '^edit$|') WHERE module = 'Student' AND plugin = 'Students' AND controller = 'Students' AND header = 'General' AND title = 'Overview';
UPDATE navigations set pattern = replace(pattern,'^edit$|^add$|' , '^edit$|') WHERE module = 'Staff' AND plugin = 'Staff' AND controller = 'Staff' AND header = 'General' AND title = 'Overview';

-- Move the Memberships and Licenses menu items in the Staff module from General
SELECT MIN(navigations.order) into @prevMembershipOrder FROM navigations WHERE title = 'Memberships' OR title = 'Licenses';
UPDATE navigations SET navigations.order = navigations.order - 2 WHERE navigations.order >= @prevMembershipOrder;
SELECT MIN(navigations.order) into @prevAwardsOrder FROM navigations WHERE module = 'Staff' AND title = 'Awards';
UPDATE navigations SET navigations.order = navigations.order + 2 WHERE navigations.order > @prevAwardsOrder;
UPDATE navigations SET navigations.order = @prevAwardsOrder + 1 WHERE title = 'Memberships';
UPDATE navigations SET navigations.order = @prevAwardsOrder + 2 WHERE title = 'Licenses';
UPDATE navigations SET header = 'General' WHERE title = 'Memberships' OR title = 'Licenses';

-- need to handle for security_functions too
SELECT MIN(security_functions.order) into @prevMembershipOrder FROM security_functions WHERE name = 'Memberships' OR name = 'Licenses';
UPDATE security_functions SET security_functions.order = security_functions.order - 2 WHERE security_functions.order >= @prevMembershipOrder;
SELECT MIN(security_functions.order) into @prevAwardsOrder FROM security_functions WHERE module = 'Staff' AND name = 'Awards';
UPDATE security_functions SET security_functions.order = security_functions.order + 2 WHERE security_functions.order > @prevAwardsOrder;
UPDATE security_functions SET security_functions.order = @prevAwardsOrder + 1 WHERE name = 'Memberships';
UPDATE security_functions SET security_functions.order = @prevAwardsOrder + 2 WHERE name = 'Licenses';
UPDATE security_functions SET category = 'General' WHERE name = 'Memberships' OR name = 'Licenses';

-- navigations for controller2
UPDATE navigations SET action = 'contacts', pattern = 'contacts' WHERE title = 'Contacts' AND header = 'GENERAL' AND controller = 'Students';
UPDATE navigations SET action = 'identities', pattern = 'identities' WHERE title = 'Identities' AND header = 'GENERAL' AND controller = 'Students';
UPDATE navigations SET action = 'nationalities', pattern = 'nationalities' WHERE title = 'Nationalities' AND header = 'GENERAL' AND controller = 'Students';
UPDATE navigations SET action = 'languages', pattern = 'languages' WHERE title = 'Languages' AND header = 'GENERAL' AND controller = 'Students';
UPDATE navigations SET action = 'comments', pattern = 'comments' WHERE title = 'Comments' AND header = 'GENERAL' AND controller = 'Students';
UPDATE navigations SET action = 'specialNeed', pattern = '^specialNeed' WHERE title = 'Special Needs' AND header = 'GENERAL' AND controller = 'Students';
UPDATE navigations SET action = 'award', pattern = '^award' WHERE title = 'Awards' AND header = 'GENERAL' AND controller = 'Students';
UPDATE navigations SET action = 'contacts', pattern = 'contacts' WHERE title = 'Contacts' AND header = 'GENERAL' AND controller = 'Staff';
UPDATE navigations SET action = 'identities', pattern = 'identities' WHERE title = 'Identities' AND header = 'GENERAL' AND controller = 'Staff';
UPDATE navigations SET action = 'nationalities', pattern = 'nationalities' WHERE title = 'Nationalities' AND header = 'GENERAL' AND controller = 'Staff';
UPDATE navigations SET action = 'languages', pattern = 'languages' WHERE title = 'Languages' AND header = 'GENERAL' AND controller = 'Staff';
UPDATE navigations SET action = 'comments', pattern = 'comments' WHERE title = 'Comments' AND header = 'GENERAL' AND controller = 'Staff';
UPDATE navigations SET action = 'specialNeed', pattern = '^specialNeed' WHERE title = 'Special Needs' AND header = 'GENERAL' AND controller = 'Staff';
UPDATE navigations SET action = 'award', pattern = '^award' WHERE title = 'Awards' AND header = 'GENERAL' AND controller = 'Staff';
UPDATE navigations SET action = 'users', pattern = 'users|SecurityUserAccess.view' WHERE action = 'SecurityUser' AND module = 'Administration' AND controller = 'Security' AND title = 'Users';
UPDATE navigations SET action = 'password', pattern = 'password' WHERE action = 'SecurityUserLogin' AND module = 'Preferences' AND controller = 'Preferences' AND title = 'Password';

-- Security functions must be handled to converted modules
UPDATE security_functions SET _view = 'contacts|contactsView', _edit = '_view:contactsEdit', _add = '_view:contactsAdd', _delete = '_view:contactsDelete', _execute = NULL WHERE controller = 'Students' AND module = 'Students' AND category = 'General' AND name = 'Contacts';
UPDATE security_functions SET _view = 'identities|identitiesView', _edit = '_view:identitiesEdit', _add = '_view:identitiesAdd', _delete = '_view:identitiesDelete', _execute = NULL WHERE controller = 'Students' AND module = 'Students' AND category = 'General' AND name = 'Identities';
UPDATE security_functions SET _view = 'nationalities|nationalitiesView', _edit = '_view:nationalitiesEdit', _add = '_view:nationalitiesAdd', _delete = '_view:nationalitiesDelete', _execute = NULL WHERE controller = 'Students' AND module = 'Students' AND category = 'General' AND name = 'Nationalities';
UPDATE security_functions SET _view = 'languages|languagesView', _edit = '_view:languagesEdit', _add = '_view:languagesAdd', _delete = '_view:languagesDelete', _execute = NULL WHERE controller = 'Students' AND module = 'Students' AND category = 'General' AND name = 'Languages';
UPDATE security_functions SET _view = 'comments|commentsView', _edit = '_view:commentsEdit', _add = '_view:commentsAdd', _delete = '_view:commentsDelete', _execute = NULL WHERE controller = 'Students' AND module = 'Students' AND category = 'General' AND name = 'Comments';
UPDATE security_functions SET _view = 'specialNeed|specialNeedView', _edit = '_view:specialNeedEdit', _add = '_view:specialNeedAdd', _delete = '_view:specialNeedDelete', _execute = NULL WHERE controller = 'Students' AND module = 'Students' AND category = 'General' AND name = 'Needs';
UPDATE security_functions SET _view = 'award|awardView', _edit = '_view:awardEdit', _add = '_view:awardAdd', _delete = '_view:awardDelete', _execute = NULL WHERE controller = 'Students' AND module = 'Students' AND category = 'General' AND name = 'Awards';
UPDATE security_functions SET _view = 'contacts|contactsView', _edit = '_view:contactsEdit', _add = '_view:contactsAdd', _delete = '_view:contactsDelete', _execute = NULL WHERE controller = 'Staff' AND module = 'Staff' AND category = 'General' AND name = 'Contacts';
UPDATE security_functions SET _view = 'identities|identitiesView', _edit = '_view:identitiesEdit', _add = '_view:identitiesAdd', _delete = '_view:identitiesDelete', _execute = NULL WHERE controller = 'Staff' AND module = 'Staff' AND category = 'General' AND name = 'Identities';
UPDATE security_functions SET _view = 'nationalities|nationalitiesView', _edit = '_view:nationalitiesEdit', _add = '_view:nationalitiesAdd', _delete = '_view:nationalitiesDelete', _execute = NULL WHERE controller = 'Staff' AND module = 'Staff' AND category = 'General' AND name = 'Nationalities';
UPDATE security_functions SET _view = 'languages|languagesView', _edit = '_view:languagesEdit', _add = '_view:languagesAdd', _delete = '_view:languagesDelete', _execute = NULL WHERE controller = 'Staff' AND module = 'Staff' AND category = 'General' AND name = 'Languages';
UPDATE security_functions SET _view = 'comments|commentsView', _edit = '_view:commentsEdit', _add = '_view:commentsAdd', _delete = '_view:commentsDelete', _execute = NULL WHERE controller = 'Staff' AND module = 'Staff' AND category = 'General' AND name = 'Comments';
UPDATE security_functions SET _view = 'specialNeed|specialNeedView', _edit = '_view:specialNeedEdit', _add = '_view:specialNeedAdd', _delete = '_view:specialNeedDelete', _execute = NULL WHERE controller = 'Staff' AND module = 'Staff' AND category = 'General' AND name = 'Needs';
UPDATE security_functions SET _view = 'award|awardView', _edit = '_view:awardEdit', _add = '_view:awardAdd', _delete = '_view:awardDelete', _execute = NULL WHERE controller = 'Staff' AND module = 'Staff' AND category = 'General' AND name = 'Awards';

-- update security for users
SELECT security_functions.order INTO @securityUserOrder from security_functions WHERE name = 'Users' AND controller = 'Security' AND module = 'Administration';
UPDATE security_functions SET security_functions.order = security_functions.order + 1 WHERE security_functions.order >= @securityUserOrder;
INSERT security_functions SELECT * FROM z_985_security_functions WHERE name = 'List of Users' AND controller = 'Security' AND module = 'Administration';
UPDATE security_functions SET _view = 'users', _edit = NULL, _add = NULL WHERE name = 'List of Users' AND controller = 'Security' AND module = 'Administration';
UPDATE security_functions SET _view = 'usersView', _edit = '_view:usersEdit|usersAccess', _add = '_view:usersAdd' WHERE name = 'Users' AND controller = 'Security' AND module = 'Administration';




-- Updating census gender_ids
SELECT id INTO @fieldGenderMale FROM field_option_values WHERE field_option_id IN (SELECT id FROM field_options WHERE code = 'Gender') AND name = 'Male';
SELECT id INTO @fieldGenderFemale FROM field_option_values WHERE field_option_id IN (SELECT id FROM field_options WHERE code = 'Gender') AND name = 'Female';
SELECT id INTO @newGenderMale FROM genders WHERE name = 'Male';
SELECT id INTO @newGenderFemale FROM genders WHERE name = 'Female';

UPDATE census_students SET gender_id = @fieldGenderMale WHERE gender_id = @newGenderMale;
UPDATE census_attendances SET gender_id = @fieldGenderMale WHERE gender_id = @newGenderMale;
UPDATE census_behaviours SET gender_id = @fieldGenderMale WHERE gender_id = @newGenderMale;
UPDATE census_graduates SET gender_id = @fieldGenderMale WHERE gender_id = @newGenderMale;
UPDATE census_teachers SET gender_id = @fieldGenderMale WHERE gender_id = @newGenderMale;
UPDATE census_teacher_fte SET gender_id = @fieldGenderMale WHERE gender_id = @newGenderMale;
UPDATE census_teacher_training SET gender_id = @fieldGenderMale WHERE gender_id = @newGenderMale;
UPDATE census_sanitations SET gender_id = @fieldGenderMale WHERE gender_id = @newGenderMale;
UPDATE census_students SET gender_id = @fieldGenderFemale WHERE gender_id = @newGenderFemale;
UPDATE census_attendances SET gender_id = @fieldGenderFemale WHERE gender_id = @newGenderFemale;
UPDATE census_behaviours SET gender_id = @fieldGenderFemale WHERE gender_id = @newGenderFemale;
UPDATE census_graduates SET gender_id = @fieldGenderFemale WHERE gender_id = @newGenderFemale;
UPDATE census_teachers SET gender_id = @fieldGenderFemale WHERE gender_id = @newGenderFemale;
UPDATE census_teacher_fte SET gender_id = @fieldGenderFemale WHERE gender_id = @newGenderFemale;
UPDATE census_teacher_training SET gender_id = @fieldGenderFemale WHERE gender_id = @newGenderFemale;
UPDATE census_sanitations SET gender_id = @fieldGenderFemale WHERE gender_id = @newGenderFemale;

RENAME TABLE z_985_student_identities TO student_identities;
RENAME TABLE z_985_student_nationalities TO student_nationalities;
RENAME TABLE z_985_student_languages TO student_languages;
RENAME TABLE z_985_student_comments TO student_comments;
RENAME TABLE z_985_student_special_needs TO student_special_needs;
RENAME TABLE z_985_student_awards TO student_awards;
RENAME TABLE z_985_student_contacts TO student_contacts;
RENAME TABLE z_985_staff_identities TO staff_identities;
RENAME TABLE z_985_staff_nationalities TO staff_nationalities;
RENAME TABLE z_985_staff_languages TO staff_languages;
RENAME TABLE z_985_staff_comments TO staff_comments;
RENAME TABLE z_985_staff_special_needs TO staff_special_needs;
RENAME TABLE z_985_staff_awards TO staff_awards;
RENAME TABLE z_985_staff_contacts TO staff_contacts;

ALTER TABLE `student_comments` CHANGE `comment_date` `comment_date` DATETIME NOT NULL;
ALTER TABLE `staff_comments` CHANGE `comment_date` `comment_date` DATETIME NOT NULL;


-- create new tables to combined tables
DROP TABLE user_identities;
DROP TABLE user_nationalities;
DROP TABLE user_languages;
DROP TABLE user_comments;
DROP TABLE user_special_needs;
DROP TABLE user_awards;
DROP TABLE user_contacts;

DROP TABLE students;
RENAME TABLE z_985_students TO students;
DROP TABLE staff;
RENAME TABLE z_985_staff TO staff;
DROP TABLE security_users;
RENAME TABLE z_985_security_users TO security_users;

DROP TABLE genders;


