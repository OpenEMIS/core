-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2338', NOW());

CREATE TABLE IF NOT EXISTS z2338_institution_section_students LIKE institution_section_students;
INSERT INTO z2338_institution_section_students SELECT * FROM institution_section_students WHERE NOT EXISTS (SELECT * FROM z2338_institution_section_students);

DELETE FROM institution_section_students WHERE institution_section_students.status = 0;
ALTER TABLE `institution_section_students` DROP `status`;

ALTER TABLE `institution_section_students` CHANGE `id` `id` VARCHAR(36) NOT NULL;
UPDATE institution_section_students SET id = uuid();