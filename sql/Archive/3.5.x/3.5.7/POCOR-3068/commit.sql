-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3068', NOW());


-- code here
DELETE FROM `security_functions` WHERE `security_functions`.`id` = 5032;
DELETE FROM `security_functions` WHERE `security_functions`.`id` = 5033;