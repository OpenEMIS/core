-- security_functions
DELETE FROM `security_functions` WHERE `id` = 5056;
UPDATE `security_functions` SET `order` = 5009 WHERE `id` = 5009;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3605';
