-- security_rest_sessions
DROP TABLE `security_rest_sessions`;

ALTER TABLE `z_2843_security_rest_sessions` 
RENAME TO  `security_rest_sessions` ;

-- authentication_type_attributes
ALTER TABLE `authentication_type_attributes` 
CHANGE COLUMN `attribute_name` `attribute_name` VARCHAR(50) NOT NULL COMMENT '' ;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2843';