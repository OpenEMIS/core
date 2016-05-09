-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-2907', NOW());

-- backup tables
CREATE TABLE z_2907_backup_institution_staff LIKE institution_staff;
INSERT INTO z_2907_backup_institution_staff SELECT * FROM institution_staff;

CREATE TABLE z_2907_backup_security_group_users LIKE security_group_users;
INSERT INTO z_2907_backup_security_group_users SELECT * FROM security_group_users;

-- temporary table for processing
CREATE TABLE z_2907_institution_staff LIKE institution_staff;
-- inserting all non-expired entries
INSERT INTO z_2907_institution_staff SELECT * FROM institution_staff
    WHERE (institution_staff.end_date IS NULL OR institution_staff.end_date >= CURDATE());
    ;

-- destroy all system role related data pt1/2
UPDATE z_2907_institution_staff SET security_group_user_id = NULL;

ALTER TABLE `z_2907_institution_staff` 
ADD `staff_position_titles_id` INT NOT NULL AFTER `institution_position_id`, 
ADD `security_role_id` INT NOT NULL AFTER `staff_position_titles_id`, 
ADD `security_group_id` INT NOT NULL AFTER `security_role_id`,
ADD `is_homeroom` INT NOT NULL AFTER `security_group_id`,
ADD `security_group_user_homeroom_id` char(36) NULL AFTER `is_homeroom`
;

-- updating the temp table with security role information
SET @uuid:= 0;
UPDATE z_2907_institution_staff 
    INNER JOIN institution_positions ON (z_2907_institution_staff.institution_position_id = institution_positions.id)
    INNER JOIN staff_position_titles ON (institution_positions.staff_position_title_id = staff_position_titles.id)
    INNER JOIN institutions ON (z_2907_institution_staff.institution_id = institutions.id)
        SET 
            `z_2907_institution_staff`.`staff_position_titles_id` = `staff_position_titles`.`id`,
            `z_2907_institution_staff`.`security_role_id` = `staff_position_titles`.`security_role_id`,
            `z_2907_institution_staff`.`security_group_id` = `institutions`.`security_group_id`,
            `z_2907_institution_staff`.`is_homeroom` = `institution_positions`.`is_homeroom`,
            `z_2907_institution_staff`.`security_group_user_id` = CONCAT('uuid-', LPAD(z_2907_institution_staff.id, 31, '0'))
            ;

UPDATE z_2907_institution_staff 
    SET 
        `z_2907_institution_staff`.`security_group_user_homeroom_id` = CONCAT('uuid-', 'h-', LPAD(z_2907_institution_staff.id, 29, '0'))
            WHERE `z_2907_institution_staff`.`is_homeroom` = 1;

-- destroy all system role related data pt2/2
DELETE FROM security_group_users WHERE EXISTS (SELECT * FROM institutions WHERE institutions.security_group_id = security_group_users.security_group_id);

-- INSERTION FOR THE ACTUAL ROLE ROW
INSERT INTO security_group_users (
        `security_group_users`.`id`, 
        `security_group_users`.`security_group_id`, 
        `security_group_users`.`security_user_id`, 
        `security_group_users`.`security_role_id`, 
        `security_group_users`.`created_user_id`, 
        `security_group_users`.`created`
    ) 
    SELECT 
        `security_group_user_id`, 
        `security_group_id`, 
        `staff_id`, 
        `security_role_id`, 
        `created_user_id`, 
        `created`
              FROM z_2907_institution_staff 
                WHERE z_2907_institution_staff.security_group_user_id IS NOT NULL;

-- INSERTION FOR IS_HOMEROOM
SET @homeroomId := 0;
SELECT `id` INTO @homeroomId FROM security_roles WHERE code = 'HOMEROOM_TEACHER';
INSERT INTO security_group_users (
        `security_group_users`.`id`, 
        `security_group_users`.`security_group_id`, 
        `security_group_users`.`security_user_id`, 
        `security_group_users`.`security_role_id`, 
        `security_group_users`.`created_user_id`, 
        `security_group_users`.`created`
    ) 
    SELECT 
        `security_group_user_homeroom_id`, 
        `security_group_id`, 
        `staff_id`, 
        @homeroomId, 
        `created_user_id`, 
        `created`
              FROM z_2907_institution_staff
                WHERE z_2907_institution_staff.is_homeroom = 1;



-- HAVE TO UPDATE THE ACTUAL TABLE institution_staff WITH THE security_group_user_id
UPDATE institution_staff 
    INNER JOIN z_2907_institution_staff ON (z_2907_institution_staff.id = institution_staff.id)
        SET institution_staff.security_group_user_id = z_2907_institution_staff.security_group_user_id;

