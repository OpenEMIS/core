-- PHPOE-2082
INSERT INTO `db_patches` VALUES ('PHPOE-2082');

CREATE TABLE `z2082_import_mapping` LIKE `import_mapping`;
INSERT INTO `z2082_import_mapping` SELECT * FROM `import_mapping`;

UPDATE `import_mapping` set `import_mapping`.`plugin` = 'Student' where  `import_mapping`.`model` = 'Student';
UPDATE `import_mapping` set `import_mapping`.`model` = 'Students' where  `import_mapping`.`model` = 'Student';

UPDATE `import_mapping` 
	set 
		`import_mapping`.`lookup_plugin` = 'User', 
		`import_mapping`.`lookup_alias` = 'Genders', 
		`import_mapping`.`lookup_model` = 'Genders' 
where  `import_mapping`.`lookup_model` = 'Gender' and `import_mapping`.`model` = 'Students' and `import_mapping`.`plugin` = 'Student';
