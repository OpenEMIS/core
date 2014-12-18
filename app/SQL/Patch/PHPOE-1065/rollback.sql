UPDATE 	`config_items`
SET 	`label` = 'Student Identification'
WHERE 	`config_items`.`name` = 'student_identification'
		AND `config_items`.`type` = 'Custom Validation';

UPDATE 	`config_items`
SET 	`label` = 'Staff Identification'
WHERE 	`config_items`.`name` = 'staff_identification'
		AND `config_items`.`type` = 'Custom Validation';