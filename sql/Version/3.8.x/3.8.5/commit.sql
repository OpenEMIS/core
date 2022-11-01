-- POCOR-3663
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3663', NOW());

-- institution_rooms
RENAME TABLE `institution_rooms` TO `z_3663_institution_rooms`;

DROP TABLE IF EXISTS `institution_rooms`;
CREATE TABLE IF NOT EXISTS `institution_rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `start_year` int(4) NOT NULL,
  `end_date` date NOT NULL,
  `end_year` int(4) NOT NULL,
  `room_status_id` int(11) NOT NULL,
  `institution_infrastructure_id` int(11) NOT NULL,
  `institution_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `room_type_id` int(11) NOT NULL,
  `infrastructure_condition_id` int(11) NOT NULL,
  `previous_room_id` int(11) NOT NULL COMMENT 'links to institution_rooms.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `code` (`code`),
  INDEX `room_status_id` (`room_status_id`),
  INDEX `institution_infrastructure_id` (`institution_infrastructure_id`),
  INDEX `institution_id` (`institution_id`),
  INDEX `academic_period_id` (`academic_period_id`),
  INDEX `room_type_id` (`room_type_id`),
  INDEX `infrastructure_condition_id` (`infrastructure_condition_id`),
  INDEX `previous_room_id` (`previous_room_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `institution_rooms` SELECT * FROM `z_3663_institution_rooms`;

DELETE FROM `institution_rooms` WHERE `start_date` > `end_date`;


-- POCOR-3668
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3668', NOW());

-- examination_centre_rooms
ALTER TABLE `examination_centre_rooms` CHANGE `size` `size` INT(3) NULL DEFAULT '0';


-- 3.8.5
UPDATE config_items SET value = '3.8.5' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
