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

-- PHPOE-1378

UPDATE `security_functions` SET
`_add` = '_view:'
WHERE `controller` = 'Census' AND `module` = 'Institutions' AND `category` = 'Totals' AND `_view` = 'finances' LIMIT 1;
