-- db_patches
INSERT INTO `db_patches` VALUES('POCOR-2612', NOW());

-- institution_staff
ALTER TABLE `institution_staff` ADD `security_group_user_id` CHAR(36) NULL AFTER `institution_position_id`;
ALTER TABLE `institution_staff` ADD INDEX(`security_group_user_id`);

-- system_processes
DROP TABLE IF EXISTS `system_processes`;

CREATE TABLE `system_processes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `process_id` int(11) DEFAULT NULL,
  `callable_event` varchar(50) NULL,
  `status` int(2) NOT NULL COMMENT '1 => New\n2 => Running\n3 => Completed\n-1 => Abort\n-2 => Error',
  `executed_count` int(3) NOT NULL DEFAULT 0,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `params` text DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- patch security_group_user_id

CREATE TABLE `z_2612_security_group_users` LIKE `security_group_users`;
INSERT INTO `z_2612_security_group_users` SELECT * FROM `security_group_users`;
DELETE FROM `security_group_users` WHERE `security_group_id` IN (
    SELECT `security_group_id` FROM `institutions`
);

ALTER TABLE `security_group_users` 
ADD COLUMN `institution_staff_id` INT NULL COMMENT '' AFTER `created`;

INSERT INTO `security_group_users`
SELECT 
    uuid(),
    `Institutions`.`security_group_id` as security_group_id,
    `InstitutionStaff`.`staff_id` as security_user_id, 
    `StaffPositionTitles`.`security_role_id` as security_role_id,
    1,
    NOW(),
    `InstitutionStaff`.`id` as institution_staff_id
FROM `institution_staff` `InstitutionStaff`
INNER JOIN `Institutions`
    ON `Institutions`.`id` = `InstitutionStaff`.`institution_id`
INNER JOIN `institution_positions` `Positions`
    ON `Positions`.`id` = `InstitutionStaff`.`institution_position_id`
INNER JOIN `staff_position_titles` `StaffPositionTitles`
    ON `StaffPositionTitles`.`id` = `Positions`.`staff_position_title_id`
    AND `StaffPositionTitles`.`security_role_id` <> 0;
    
UPDATE `institution_staff` 
INNER JOIN `security_group_users`
    ON `security_group_users`.`institution_staff_id` = `institution_staff`.`id`
SET `institution_staff`.`security_group_user_id` = `security_group_users`.`id`;

ALTER TABLE `security_group_users` 
DROP COLUMN `institution_staff_id`;