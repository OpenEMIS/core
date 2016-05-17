DROP TABLE security_group_users;
RENAME TABLE z_3003_security_group_users TO security_group_users;
DROP TABLE institution_staff;
RENAME TABLE z_3003_institution_staff TO institution_staff;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3003';