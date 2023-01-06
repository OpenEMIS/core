-- POCOR-1967
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-1967', NOW());

-- institution_subjects_rooms
DROP TABLE IF EXISTS `institution_subjects_rooms`;
CREATE TABLE IF NOT EXISTS `institution_subjects_rooms` (
  `id` char(36) NOT NULL,
  `institution_subject_id` int(11) NOT NULL COMMENT 'links to institution_subjects.id',
  `institution_room_id` int(11) NOT NULL COMMENT 'links to institution_rooms.id',
  PRIMARY KEY (`id`),
  KEY `institution_subject_id` (`institution_subject_id`),
  KEY `institution_room_id` (`institution_room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- room_types
UPDATE `room_types` SET `order` = `order` + 1, `default` = 0;

INSERT INTO `room_types` (`name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('Classroom', 1, 1, 0, 1, 'CLASSROOM', 'CLASSROOM', NULL, NULL, 2, NOW());


-- 3.7.3
UPDATE config_items SET value = '3.7.3' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
