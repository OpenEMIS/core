ALTER TABLE `countries` DROP `identity_type_id`;

ALTER TABLE `user_identities` CHANGE `issue_date` `issue_date` DATE NOT NULL;
ALTER TABLE `user_identities` CHANGE `expiry_date` `expiry_date` DATE NOT NULL;
ALTER TABLE `user_identities` CHANGE `issue_location` `issue_location` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;


-- We should remove config_items (student_identities, student_nationalities, staff_identities, staff_nationalities) (rollback)
INSERT INTO config_items SELECT * FROM z1272_config_items;


-- Removing is_wizard (rollback)
UPDATE navigations SET is_wizard = 1 WHERE action = 'StudentNationality';
UPDATE navigations SET is_wizard = 1 WHERE action = 'StudentIdentity';
UPDATE navigations SET is_wizard = 1 WHERE action = 'StaffNationality';
UPDATE navigations SET is_wizard = 1 WHERE action = 'StaffIdentity';

