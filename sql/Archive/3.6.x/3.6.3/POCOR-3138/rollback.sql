-- security_user
ALTER TABLE `security_users` DROP `identity_number`;

-- translations
DELETE FROM `translations`
WHERE `en` = 'Please set other identity type as default before deleting the current one';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3138';