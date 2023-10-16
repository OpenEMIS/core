DROP TABLE institution_section_students;
RENAME TABLE z2338_institution_section_students TO institution_section_students;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2338';	