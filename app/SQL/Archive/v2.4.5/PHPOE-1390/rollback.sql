UPDATE config_items SET name = "student_identities" WHERE name = "StudentIdentity";
UPDATE config_items SET name = "student_nationalities" WHERE name = "StudentNationality";
UPDATE config_items SET name = "student_contacts" WHERE name = "StudentContact";
UPDATE config_items SET name = "student_specialNeed" WHERE name = "StudentSpecialNeed";

-- renaming
UPDATE config_items SET type = "Wizard - Add New Staff" WHERE type = "Add New Staff";
UPDATE config_items SET type = "Wizard - Add New Student" WHERE type = "Add New Student";

-- making sure left nav is correct
UPDATE navigations SET visible = '0' WHERE navigations.controller = 'Students' AND navigations.title = 'Add Student' AND action = 'add';