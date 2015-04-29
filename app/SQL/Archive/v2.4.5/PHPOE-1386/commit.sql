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