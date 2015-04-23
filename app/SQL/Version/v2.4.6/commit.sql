-- PHPOE-1272

ALTER TABLE `countries` ADD `identity_type_id` INT NULL AFTER `name`;

ALTER TABLE `user_identities` CHANGE `issue_date` `issue_date` DATE NULL;
ALTER TABLE `user_identities` CHANGE `expiry_date` `expiry_date` DATE NULL;
ALTER TABLE `user_identities` CHANGE `issue_location` `issue_location` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;

-- Removing is_wizard
UPDATE navigations SET is_wizard = 0 WHERE action = 'StudentNationality';
UPDATE navigations SET is_wizard = 0 WHERE action = 'StudentIdentity';
UPDATE navigations SET is_wizard = 0 WHERE action = 'StaffNationality';
UPDATE navigations SET is_wizard = 0 WHERE action = 'StaffIdentity';

-- PHPOE-1378

UPDATE `security_functions` SET 
`_add` = '_view:financesAdd' 
WHERE `controller` = 'Census' AND `module` = 'Institutions' AND `category` = 'Totals' AND `_view` = 'finances' LIMIT 1;
