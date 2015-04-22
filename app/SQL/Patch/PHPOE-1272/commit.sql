ALTER TABLE `countries` ADD `identity_type_id` INT NULL AFTER `name`;

ALTER TABLE `user_identities` CHANGE `issue_date` `issue_date` DATE NULL;
ALTER TABLE `user_identities` CHANGE `expiry_date` `expiry_date` DATE NULL;
ALTER TABLE `user_identities` CHANGE `issue_location` `issue_location` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;



-- We should remove config_items (student_identities, student_nationalities, staff_identities, staff_nationalities)
CREATE TABLE z1272_config_items LIKE config_items;
INSERT INTO z1272_config_items SELECT * FROM config_items WHERE type = 'Wizard - Add New Student' AND name = 'student_nationalities';
INSERT INTO z1272_config_items SELECT * FROM config_items WHERE type = 'Wizard - Add New Student' AND name = 'student_identities';
INSERT INTO z1272_config_items SELECT * FROM config_items WHERE type = 'Wizard - Add New Staff' AND name = 'staff_nationalities';
INSERT INTO z1272_config_items SELECT * FROM config_items WHERE type = 'Wizard - Add New Staff' AND name = 'staff_identities';

-- Removing is_wizard
UPDATE navigations SET is_wizard = 0 WHERE action = 'StudentNationality';
UPDATE navigations SET is_wizard = 0 WHERE action = 'StudentIdentity';
UPDATE navigations SET is_wizard = 0 WHERE action = 'StaffNationality';
UPDATE navigations SET is_wizard = 0 WHERE action = 'StaffIdentity';

