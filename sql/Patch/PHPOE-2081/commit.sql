-- PHPOE-2081
INSERT INTO `db_patches` VALUES ('PHPOE-2081', NOW());

CREATE TABLE `z2081_import_mapping` LIKE `import_mapping`;
INSERT INTO `z2081_import_mapping` SELECT * FROM `import_mapping`;

ALTER TABLE `import_mapping` ADD `lookup_plugin` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `foreign_key`;
ALTER TABLE `import_mapping` CHANGE `foreign_key` `foreign_key` INT(11) NULL DEFAULT '0' COMMENT '0: not foreign key, 1: field options, 2: direct table';

UPDATE `import_mapping` set `import_mapping`.`model` = 'Institutions' where  `import_mapping`.`model` = 'InstitutionSite';
UPDATE `import_mapping` set `import_mapping`.`lookup_plugin` = 'Area' where  `import_mapping`.`lookup_model` = 'Area';
UPDATE `import_mapping` set `import_mapping`.`lookup_model` = 'Areas' where  `import_mapping`.`lookup_model` = 'Area';
UPDATE `import_mapping` set `import_mapping`.`lookup_plugin` = 'Area' where  `import_mapping`.`lookup_model` = 'AreaAdministrative';
UPDATE `import_mapping` set `import_mapping`.`lookup_model` = 'AreaAdministratives' where  `import_mapping`.`lookup_model` = 'AreaAdministrative';
UPDATE `import_mapping` set `import_mapping`.`lookup_plugin` = 'Institution' where  `import_mapping`.`lookup_model` = 'InstitutionSiteType';
UPDATE `import_mapping` set `import_mapping`.`lookup_model` = 'Types' where  `import_mapping`.`lookup_model` = 'InstitutionSiteType';
UPDATE `import_mapping` set `import_mapping`.`lookup_plugin` = 'Institution' where  `import_mapping`.`lookup_model` = 'InstitutionSiteStatus';
UPDATE `import_mapping` set `import_mapping`.`lookup_model` = 'Statuses' where  `import_mapping`.`lookup_model` = 'InstitutionSiteStatus';
UPDATE `import_mapping` set `import_mapping`.`lookup_plugin` = 'Institution' where  `import_mapping`.`lookup_model` = 'InstitutionSiteSector';
UPDATE `import_mapping` set `import_mapping`.`lookup_model` = 'Sectors' where  `import_mapping`.`lookup_model` = 'InstitutionSiteSector';
UPDATE `import_mapping` set `import_mapping`.`lookup_plugin` = 'Institution' where  `import_mapping`.`lookup_model` = 'InstitutionSiteProvider';
UPDATE `import_mapping` set `import_mapping`.`lookup_model` = 'Providers' where  `import_mapping`.`lookup_model` = 'InstitutionSiteProvider';
UPDATE `import_mapping` set `import_mapping`.`lookup_plugin` = 'Institution' where  `import_mapping`.`lookup_model` = 'InstitutionSiteOwnership';
UPDATE `import_mapping` set `import_mapping`.`lookup_model` = 'Ownerships' where  `import_mapping`.`lookup_model` = 'InstitutionSiteOwnership';
UPDATE `import_mapping` set `import_mapping`.`lookup_plugin` = 'Institution' where  `import_mapping`.`lookup_model` = 'InstitutionSiteLocality';
UPDATE `import_mapping` set `import_mapping`.`lookup_model` = 'Localities' where  `import_mapping`.`lookup_model` = 'InstitutionSiteLocality';
UPDATE `import_mapping` set `import_mapping`.`lookup_plugin` = 'Institution' where  `import_mapping`.`lookup_model` = 'InstitutionSiteGender';
UPDATE `import_mapping` set `import_mapping`.`lookup_model` = 'Genders' where  `import_mapping`.`lookup_model` = 'InstitutionSiteGender';
UPDATE `import_mapping` set `import_mapping`.`model` = 'Students' where  `import_mapping`.`model` = 'Student';
UPDATE `import_mapping` 
	set 
		`import_mapping`.`lookup_plugin` = 'User', 
		`import_mapping`.`lookup_model` = 'Genders' 
where  `import_mapping`.`lookup_model` = 'Gender' and (`import_mapping`.`model` = 'Students' or `import_mapping`.`model` = 'Staff');

INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `foreign_key`) values('Students', 'is_student', '(Leave this blank)', '13', '0');
INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `foreign_key`) values('Staff', 'is_staff', '(Leave this blank)', '13', '0');
