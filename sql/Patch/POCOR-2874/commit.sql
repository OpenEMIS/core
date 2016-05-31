 INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-2874', NOW());
 
 -- remove orphan
DELETE FROM `staff_custom_field_values` 
WHERE NOT EXISTS ( 
	SELECT 1 FROM `staff_custom_fields`
		WHERE `staff_custom_fields`.`id` = `staff_custom_field_values`.`staff_custom_field_id`
	);