-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3031', NOW());

-- remove orphan record
DELETE FROM `institution_staff_position_profiles` 
WHERE NOT EXISTS ( 
	SELECT 1 FROM `institution_staff`
		WHERE `institution_staff`.`id` = `institution_staff_position_profiles`.`institution_staff_id`
	);