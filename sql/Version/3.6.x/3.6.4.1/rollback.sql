-- POCOR-3396
DROP TABLE `security_user_logins`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3396';


-- 3.6.4
UPDATE config_items SET value = '3.6.4' WHERE code = 'db_version';
