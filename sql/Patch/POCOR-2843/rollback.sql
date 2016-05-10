-- security_rest_sessions
DROP TABLE `security_rest_sessions`;

ALTER TABLE `z_2843_security_rest_sessions` 
RENAME TO  `security_rest_sessions` ;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2843';