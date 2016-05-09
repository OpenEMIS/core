-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-2899', NOW());

-- remove orphan / test record
DELETE FROM `institution_staff_position_profiles` 
WHERE NOT EXISTS ( 
	SELECT 1 FROM `institutions`
		WHERE `institutions`.`id` = `institution_staff_position_profiles`.`institution_id`
	);