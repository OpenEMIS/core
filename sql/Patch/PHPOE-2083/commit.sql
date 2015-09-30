-- PHPOE-2083
INSERT INTO `db_patches` VALUES ('PHPOE-2083');

CREATE TABLE `z2083_import_mapping` LIKE `import_mapping`;
INSERT INTO `z2083_import_mapping` SELECT * FROM `import_mapping`;

UPDATE `import_mapping` set `import_mapping`.`plugin` = 'Staff' where  `import_mapping`.`model` = 'Staff';

UPDATE `import_mapping` 
	set 
		`import_mapping`.`lookup_plugin` = 'User', 
		`import_mapping`.`lookup_alias` = 'Genders', 
		`import_mapping`.`lookup_model` = 'Genders' 
where  `import_mapping`.`lookup_model` = 'Gender' and `import_mapping`.`model` = 'Staff' and `import_mapping`.`plugin` = 'Staff';
