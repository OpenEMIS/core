-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-1967', NOW());

-- institution_subjects_rooms
DROP TABLE IF EXISTS `institution_subjects_rooms`;
CREATE TABLE IF NOT EXISTS `institution_subjects_rooms` (
  `id` char(36) NOT NULL,
  `institution_subject_id` int(11) NOT NULL COMMENT 'links to custom_forms.id',
  `institution_room_id` int(11) NOT NULL COMMENT 'links to custom_forms.id',
  PRIMARY KEY (`id`),
  KEY `institution_subject_id` (`institution_subject_id`),
  KEY `institution_room_id` (`institution_room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- room_types
UPDATE `room_types` SET `default` = 0;

SET @order := 0;
SELECT MAX(`order`) INTO @order FROM `room_types`;
SET @order := @order +1;

INSERT INTO `room_types` (`name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('Classroom', @order, 1, 0, 1, 'CLASSROOM', 'CLASSROOM', NULL, NULL, 2, NOW());
