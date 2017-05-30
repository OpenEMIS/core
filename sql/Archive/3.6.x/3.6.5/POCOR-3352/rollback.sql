-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3352';

-- institution_shifts
ALTER TABLE `institution_shifts` DROP `previous_shift_id`;