-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2338';	

DROP TABLE institution_section_students;
RENAME TABLE z2338_institution_section_students TO institution_section_students;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2435';

-- security_functions
DELETE FROM `security_functions` WHERE `id` = 1038;

UPDATE config_items SET value = '3.4.7' WHERE code = 'db_version';
