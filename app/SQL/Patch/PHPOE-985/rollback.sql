-- Move the Memberships and Licenses menu items in the Staff module from General
SELECT MIN(navigations.order) into @prevMembershipOrder FROM navigations WHERE title = 'Memberships' OR title = 'Licenses';
UPDATE navigations SET navigations.order = navigations.order - 2 WHERE navigations.order >= @prevMembershipOrder;
SELECT MIN(navigations.order) into @prevAwardsOrder FROM navigations WHERE title = 'Awards';
UPDATE navigations SET navigations.order = navigations.order + 2 WHERE navigations.order > @prevAwardsOrder;
UPDATE navigations SET navigations.order = @prevAwardsOrder + 1 WHERE title = 'Memberships';
UPDATE navigations SET navigations.order = @prevAwardsOrder + 2 WHERE title = 'Licenses';
UPDATE navigations SET header = 'General' WHERE title = 'Memberships' OR title = 'Licenses';

-- navigations for controller2
UPDATE navigations SET action = 'contacts', pattern = 'contacts' WHERE title = 'Contacts' AND header = 'GENERAL' AND controller = 'Students';
UPDATE navigations SET action = 'identities', pattern = 'identities' WHERE title = 'Identities' AND header = 'GENERAL' AND controller = 'Students';
UPDATE navigations SET action = 'nationalities', pattern = 'nationalities' WHERE title = 'Nationalities' AND header = 'GENERAL' AND controller = 'Students';
UPDATE navigations SET action = 'languages', pattern = 'languages' WHERE title = 'Languages' AND header = 'GENERAL' AND controller = 'Students';
UPDATE navigations SET action = 'comments', pattern = 'comments' WHERE title = 'Comments' AND header = 'GENERAL' AND controller = 'Students';
UPDATE navigations SET action = 'specialNeed', pattern = '^specialNeed' WHERE title = 'Special Needs' AND header = 'GENERAL' AND controller = 'Students';
UPDATE navigations SET action = 'award', pattern = '^atAward' WHERE title = 'Awards' AND header = 'GENERAL' AND controller = 'Students';
UPDATE navigations SET action = 'contacts', pattern = 'contacts' WHERE title = 'Contacts' AND header = 'GENERAL' AND controller = 'Staff';
UPDATE navigations SET action = 'identities', pattern = 'identities' WHERE title = 'Identities' AND header = 'GENERAL' AND controller = 'Staff';
UPDATE navigations SET action = 'nationalities', pattern = 'nationalities' WHERE title = 'Nationalities' AND header = 'GENERAL' AND controller = 'Staff';
UPDATE navigations SET action = 'languages', pattern = 'languages' WHERE title = 'Languages' AND header = 'GENERAL' AND controller = 'Staff';
UPDATE navigations SET action = 'comments', pattern = 'comments' WHERE title = 'Comments' AND header = 'GENERAL' AND controller = 'Staff';
UPDATE navigations SET action = 'specialNeed', pattern = '^specialNeed' WHERE title = 'Special Needs' AND header = 'GENERAL' AND controller = 'Staff';
UPDATE navigations SET action = 'award', pattern = '^award' WHERE title = 'Awards' AND header = 'GENERAL' AND controller = 'Staff';

-- ~~comment related sql
ALTER TABLE `student_comments` CHANGE `comment_date` `comment_date` DATETIME NOT NULL;
ALTER TABLE `staff_comments` CHANGE `comment_date` `comment_date` DATETIME NOT NULL;


--Security functions must be handled to converted modules
UPDATE security_functions SET _view = 'contacts|contactsView', _edit = '_view:contactsEdit', _add = '_view:contactsAdd', _delete = '_view:contactsDelete', _execute = NULL WHERE controller = 'Students' AND module = 'Students' AND category = 'Details' AND name = 'Contacts';
UPDATE security_functions SET _view = 'identities|identitiesView', _edit = '_view:identitiesEdit', _add = '_view:identitiesAdd', _delete = '_view:identitiesDelete', _execute = NULL WHERE controller = 'Students' AND module = 'Students' AND category = 'Details' AND name = 'Identities';
UPDATE security_functions SET _view = 'nationalities|nationalitiesView', _edit = '_view:nationalitiesEdit', _add = '_view:nationalitiesAdd', _delete = '_view:nationalitiesDelete', _execute = NULL WHERE controller = 'Students' AND module = 'Students' AND category = 'Details' AND name = 'Nationalities';
UPDATE security_functions SET _view = 'languages|languagesView', _edit = '_view:languagesEdit', _add = '_view:languagesAdd', _delete = '_view:languagesDelete', _execute = NULL WHERE controller = 'Students' AND module = 'Students' AND category = 'Details' AND name = 'Languages';
UPDATE security_functions SET _view = 'comments|commentsView', _edit = '_view:commentsEdit', _add = '_view:commentsAdd', _delete = '_view:commentsDelete', _execute = NULL WHERE controller = 'Students' AND module = 'Students' AND category = 'Details' AND name = 'Comments';
UPDATE security_functions SET _view = 'specialNeed|specialNeedView', _edit = '_view:specialNeedEdit', _add = '_view:specialNeedAdd', _delete = '_view:specialNeedDelete', _execute = NULL WHERE controller = 'Students' AND module = 'Students' AND category = 'Details' AND name = 'Needs';
UPDATE security_functions SET _view = 'award|awardView', _edit = '_view:awardEdit', _add = '_view:awardAdd', _delete = '_view:awardDelete', _execute = NULL WHERE controller = 'Students' AND module = 'Students' AND category = 'Details' AND name = 'Awards';
UPDATE security_functions SET _view = 'contacts|contactsView', _edit = '_view:contactsEdit', _add = '_view:contactsAdd', _delete = '_view:contactsDelete', _execute = NULL WHERE controller = 'Students' AND module = 'Students' AND category = 'Details' AND name = 'Contacts';
UPDATE security_functions SET _view = 'identities|identitiesView', _edit = '_view:identitiesEdit', _add = '_view:identitiesAdd', _delete = '_view:identitiesDelete', _execute = NULL WHERE controller = 'Students' AND module = 'Students' AND category = 'Details' AND name = 'Identities';
UPDATE security_functions SET _view = 'nationalities|nationalitiesView', _edit = '_view:nationalitiesEdit', _add = '_view:nationalitiesAdd', _delete = '_view:nationalitiesDelete', _execute = NULL WHERE controller = 'Students' AND module = 'Students' AND category = 'Details' AND name = 'Nationalities';
UPDATE security_functions SET _view = 'languages|languagesView', _edit = '_view:languagesEdit', _add = '_view:languagesAdd', _delete = '_view:languagesDelete', _execute = NULL WHERE controller = 'Students' AND module = 'Students' AND category = 'Details' AND name = 'Languages';
UPDATE security_functions SET _view = 'comments|commentsView', _edit = '_view:commentsEdit', _add = '_view:commentsAdd', _delete = '_view:commentsDelete', _execute = NULL WHERE controller = 'Students' AND module = 'Students' AND category = 'Details' AND name = 'Comments';
UPDATE security_functions SET _view = 'specialNeed|specialNeedView', _edit = '_view:specialNeedEdit', _add = '_view:specialNeedAdd', _delete = '_view:specialNeedDelete', _execute = NULL WHERE controller = 'Students' AND module = 'Students' AND category = 'Details' AND name = 'Needs';
UPDATE security_functions SET _view = 'award|awardView', _edit = '_view:awardEdit', _add = '_view:awardAdd', _delete = '_view:awardDelete', _execute = NULL WHERE controller = 'Students' AND module = 'Students' AND category = 'Details' AND name = 'Awards';

-- create new tables to combined tables
DROP TABLE user_identities;
DROP TABLE user_nationalities;
DROP TABLE user_languages;
DROP TABLE user_comments;
DROP TABLE user_special_needs;
DROP TABLE user_awards;
DROP TABLE user_contacts;

DROP TABLE students;
RENAME TABLE 985_students TO students;
DROP TABLE staff;
RENAME TABLE 985_staff TO staff;
DROP TABLE security_users;
RENAME TABLE 985_security_users TO security_users;


