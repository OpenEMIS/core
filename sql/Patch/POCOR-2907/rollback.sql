DROP TABLE institution_staff;
RENAME TABLE z_2907_backup_institution_staff TO institution_staff;
DROP TABLE security_group_users;
RENAME TABLE z_2907_backup_security_group_users TO security_group_users;

DROP TABLE z_2907_institution_staff;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2907';