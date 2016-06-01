--
-- PHPOE-2463
--

INSERT INTO `db_patches` VALUES ('PHPOE-2463', NOW());

CREATE TABLE `z_2463_institution_section_students` LIKE `institution_section_students`;
INSERT INTO `z_2463_institution_section_students` SELECT * FROM `institution_section_students`;

ALTER TABLE `institution_section_students` CHANGE `id` `id` CHAR(36) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

