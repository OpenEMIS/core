-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-2907', NOW());

-- backup tables
CREATE TABLE z_2907_backup_institution_staff LIKE institution_staff;
INSERT INTO z_2907_backup_institution_staff SELECT * FROM institution_staff;

CREATE TABLE z_2907_backup_security_group_users LIKE security_group_users;
INSERT INTO z_2907_backup_security_group_users SELECT * FROM security_group_users;

-- temporary table for processing
CREATE TABLE z_2907_institution_staff LIKE institution_staff;
ALTER TABLE `z_2907_institution_staff` 
ADD `staff_position_titles_id` INT NOT NULL AFTER `institution_position_id`, 
ADD `security_role_id` INT NOT NULL AFTER `staff_position_titles_id`, 
ADD `security_group_id` INT NOT NULL AFTER `security_role_id`,
ADD `is_homeroom` INT NOT NULL AFTER `security_group_id`,
ADD `security_group_user_homeroom_id` char(36) NULL AFTER `is_homeroom`
;

DELETE FROM security_group_users WHERE EXISTS (SELECT * FROM institutions WHERE institutions.security_group_id = security_group_users.security_group_id);

-- inserting all non-expired entries
INSERT INTO z_2907_institution_staff (
        `id`,
        `FTE`,
        `start_date`,
        `start_year`,
        `end_date`,
        `end_year`,
        `staff_id`,
        `staff_type_id`,
        `staff_status_id`,
        `institution_id`,
        `institution_position_id`,

        `staff_position_titles_id`,
        `security_role_id`,
        `security_group_id`,
        `is_homeroom`,
        `security_group_user_homeroom_id`,

        `security_group_user_id`,
        `modified_user_id`,
        `modified`,
        `created_user_id`,
        `created`
    )
    SELECT 
        `institution_staff`.`id`,
        `institution_staff`.`FTE`,
        `institution_staff`.`start_date`,
        `institution_staff`.`start_year`,
        `institution_staff`.`end_date`,
        `institution_staff`.`end_year`,
        `institution_staff`.`staff_id`,
        `institution_staff`.`staff_type_id`,
        `institution_staff`.`staff_status_id`,
        `institution_staff`.`institution_id`,
        `institution_staff`.`institution_position_id`,

        `staff_position_titles`.`id`,
        `staff_position_titles`.`security_role_id`,
        `institutions`.`security_group_id`,
        `institution_positions`.`is_homeroom`,
        CASE
            WHEN `institution_positions`.`is_homeroom` = 1
                THEN CONCAT('uuid-', 'h-', LPAD(institution_staff.id, 29, '0'))
            ELSE NULL
        END,

        CONCAT('uuid-', LPAD(institution_staff.id, 31, '0')),
        `institution_staff`.`modified_user_id`,
        `institution_staff`.`modified`,
        `institution_staff`.`created_user_id`,
        `institution_staff`.`created`
    FROM institution_staff
        INNER JOIN institution_positions ON (institution_staff.institution_position_id = institution_positions.id)
        INNER JOIN staff_position_titles ON (institution_positions.staff_position_title_id = staff_position_titles.id)
        INNER JOIN institutions ON (institution_staff.institution_id = institutions.id)
            WHERE (institution_staff.end_date IS NULL OR institution_staff.end_date >= CURDATE())
    ;


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


-- destroy all system role related data
UPDATE institution_staff SET security_group_user_id = NULL 
WHERE NOT EXISTS (
    SELECT * FROM z_2907_institution_staff WHERE institution_staff.id = z_2907_institution_staff.id
);
-- and update with new values
UPDATE institution_staff 
    INNER JOIN z_2907_institution_staff ON (z_2907_institution_staff.id = institution_staff.id)
        SET institution_staff.security_group_user_id = z_2907_institution_staff.security_group_user_id;

