-- security_user
ALTER TABLE `security_users` DROP `identity_number`;


-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3138';