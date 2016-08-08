-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2338', NOW());

CREATE TABLE IF NOT EXISTS z2338_institution_section_students LIKE institution_section_students;
INSERT INTO z2338_institution_section_students SELECT * FROM institution_section_students WHERE NOT EXISTS (SELECT * FROM z2338_institution_section_students);

DELETE FROM institution_section_students WHERE institution_section_students.status = 0;
ALTER TABLE `institution_section_students` DROP `status`;

ALTER TABLE `institution_section_students` CHANGE `id` `id` VARCHAR(36) NOT NULL;
UPDATE institution_section_students SET id = uuid();

-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2435', NOW());

-- security_functions
DELETE FROM `security_functions` WHERE `id` = 1038;
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES
(1038, 'Undo Student Status', 'Institutions', 'Institutions', 'Students', 1000, NULL, NULL, NULL, NULL, 'Undo.index|Undo.add|Undo.reconfirm', 1038, 1, 1, '0000-00-00 00:00:00');

UPDATE config_items SET value = '3.4.8' WHERE code = 'db_version';
