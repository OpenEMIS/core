--
-- PHPOE-2463
--

DROP TABLE `institution_section_students`;
ALTER TABLE `z_2463_institution_section_students` RENAME `institution_section_students`;

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2463';
