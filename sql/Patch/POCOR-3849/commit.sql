-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3849', NOW());


ALTER TABLE `room_types` ADD `classification` INT(1) NOT NULL COMMENT '0 -> Non-Classroom, 1 -> Classroom' AFTER `default`;
