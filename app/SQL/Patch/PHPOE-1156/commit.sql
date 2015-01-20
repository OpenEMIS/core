UPDATE `config_items` 
SET 
	`value`='Copyright &copy; year OpenEMIS. All rights reserved.',
	`default_value`='Copyright &copy; 2015 OpenEMIS. All rights reserved.'
WHERE 
	`name`='footer'
AND
	`type`='System';