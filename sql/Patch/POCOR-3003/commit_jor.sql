-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3003', NOW());

-- BACKUP TABLES
CREATE TABLE z_3003_security_group_users LIKE security_group_users;
INSERT INTO z_3003_security_group_users SELECT * FROM security_group_users;
CREATE TABLE z_3003_institution_staff LIKE institution_staff;
INSERT INTO z_3003_institution_staff SELECT * FROM institution_staff;


-- REMOVE NEW RECORDS
DELETE FROM security_group_users WHERE EXISTS (SELECT * FROM z_2907_institution_staff WHERE z_2907_institution_staff.security_group_user_id = security_group_users.id);

DELETE FROM security_group_users WHERE EXISTS (SELECT * FROM z_2907_institution_staff WHERE z_2907_institution_staff.security_group_user_homeroom_id = security_group_users.id);

-- ADD BACK OLD RECORDS
INSERT IGNORE INTO security_group_users SELECT * FROM z_2907_backup_security_group_users;

-- UPDATE INSTITUTION_STAFF TABLE
UPDATE institution_staff
    INNER JOIN z_2907_backup_institution_staff ON (z_2907_backup_institution_staff.id = institution_staff.id)
        SET institution_staff.security_group_user_id = z_2907_backup_institution_staff.security_group_user_id;