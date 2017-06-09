-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-4040', NOW());

ALTER TABLE `institution_rooms`
CHANGE COLUMN `room_type_id` `room_type_id` INT(11) NOT NULL COMMENT 'links to room_types.id',
CHANGE COLUMN `room_status_id` `room_status_id` INT(11) NOT NULL COMMENT 'links to infrastructure_statuses.id' ;
