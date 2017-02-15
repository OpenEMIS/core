-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3668', NOW());

-- examination_centre_rooms
ALTER TABLE `examination_centre_rooms` CHANGE `size` `size` INT(3) NULL DEFAULT '0'
