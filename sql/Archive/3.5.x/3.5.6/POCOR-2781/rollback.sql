DROP TABLE institution_subject_staff;
RENAME TABLE z_2781_institution_subject_staff TO institution_subject_staff;

-- SELECT * FROM `security_functions` WHERE `name` LIKE '%subject%' AND `category` LIKE 'Staff - Career' AND controller = 'Staff';
-- add for institution staff subjects 3014
UPDATE security_functions SET `_add` = NULL WHERE id = 3014;

-- SELECT * FROM `security_functions` WHERE `name` LIKE '%classes%' AND `category` LIKE 'Staff - Career' AND controller = 'Staff';
-- add for institution staff classes 3013
UPDATE security_functions SET `_add` = NULL WHERE id = 3013;

-- db_patches
DELETE FROM db_patches where `issue` = 'POCOR-2781';