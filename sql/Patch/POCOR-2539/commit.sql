-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2539', NOW());

-- staff_position_titles
ALTER TABLE `staff_position_titles` 
ADD COLUMN `security_role_id` INT NULL DEFAULT 0 COMMENT '' AFTER `type`;

ALTER TABLE `staff_position_titles` 
CHANGE COLUMN `security_role_id` `security_role_id` INT(11) NOT NULL COMMENT '' ,
ADD INDEX `security_role_id` (`security_role_id`);