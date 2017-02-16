DROP TABLE `security_user_logins`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3396';
