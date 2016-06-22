-- code here
DELETE FROM `security_functions` WHERE `security_functions`.`id` = 7047;


-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3037';