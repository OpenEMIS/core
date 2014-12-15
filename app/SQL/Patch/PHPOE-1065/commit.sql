UPDATE 	`config_items`
SET 	`value` = ''
WHERE 	`config_items`.`name` = 'institution_site_code'
		AND `config_items`.`type` = 'Custom Validation';

UPDATE 	`config_items`
SET 	`label` = 'Student OpenEMIS ID',
		`value` = ''
WHERE 	`config_items`.`name` = 'student_identification'
		AND `config_items`.`type` = 'Custom Validation';

UPDATE 	`config_items`
SET 	`label` = 'Staff OpenEMIS ID',
		`value` = ''
WHERE 	`config_items`.`name` = 'staff_identification'
		AND `config_items`.`type` = 'Custom Validation';