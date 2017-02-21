-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3352', NOW());

-- institution_shifts
ALTER TABLE `institution_shifts` ADD `previous_shift_id` INT NULL DEFAULT '0' COMMENT 'links to institution_shifts.id' AFTER `shift_option_id`;

UPDATE `institution_shifts`
SET `previous_shift_id` = 0;