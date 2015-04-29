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

-- making sure left nav is correct
UPDATE navigations SET visible = '1' WHERE navigations.controller = 'Students' AND navigations.title = 'Add new Student';

UPDATE navigations SET title = 'Add Existing Student' WHERE navigations.controller = 'Students' AND navigations.title = 'Add Student';
