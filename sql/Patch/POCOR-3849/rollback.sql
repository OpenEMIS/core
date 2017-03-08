ALTER TABLE `room_types` DROP `classification`;


-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3849';
