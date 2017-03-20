-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3680', NOW());

-- patch security_group_user_id
CREATE TABLE `z_3680_security_group_users` LIKE `security_group_users`;

INSERT INTO `z_3680_security_group_users` 
SELECT * FROM `security_group_users`;

DELETE FROM `security_group_users` 
WHERE EXISTS (
    SELECT 1
    FROM `institutions`
    WHERE `security_group_id` = `security_group_users`.`security_group_id`
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
INNER JOIN `institutions` `Institutions`
    ON `Institutions`.`id` = `InstitutionStaff`.`institution_id`
INNER JOIN `institution_positions` `Positions`
    ON `Positions`.`id` = `InstitutionStaff`.`institution_position_id`
INNER JOIN `staff_position_titles` `StaffPositionTitles`
    ON `StaffPositionTitles`.`id` = `Positions`.`staff_position_title_id`
    AND `StaffPositionTitles`.`security_role_id` <> 0
WHERE `InstitutionStaff`.`staff_status_id` = 1; #assigned staff only

CREATE TABLE `z_3680_institution_staff` LIKE `institution_staff`;
INSERT INTO `z_3680_institution_staff` 
SELECT * FROM `institution_staff`;

UPDATE `institution_staff`
SET `security_group_user_id` = NULL;

UPDATE `institution_staff` 
INNER JOIN `security_group_users`
    ON `security_group_users`.`institution_staff_id` = `institution_staff`.`id`
SET `institution_staff`.`security_group_user_id` = `security_group_users`.`id`;

ALTER TABLE `security_group_users` 
DROP COLUMN `institution_staff_id`;
