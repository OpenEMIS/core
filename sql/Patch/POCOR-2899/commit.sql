-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-2899', NOW());

-- remove orphan / test record
DELETE FROM `institution_staff_position_profiles` 
WHERE `id` = 17;