-- security_users
ALTER TABLE `security_users`
DROP COLUMN `identity_type_id`,
DROP COLUMN `nationality_id`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3593';
