-- PHPOE-2081
INSERT INTO `db_patches` VALUES ('PHPOE-2081');

CREATE TABLE `z2081_import_mapping` LIKE `import_mapping`;
INSERT INTO `z2081_import_mapping` SELECT * FROM `import_mapping`;

ALTER TABLE `import_mapping` ADD `plugin` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `id`;
ALTER TABLE `import_mapping` ADD `lookup_plugin` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `foreign_key`;
ALTER TABLE `import_mapping` ADD `lookup_alias` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `lookup_model`;

UPDATE `import_mapping` set `import_mapping`.`plugin` = 'Institution' where  `import_mapping`.`model` = 'InstitutionSite';
UPDATE `import_mapping` set `import_mapping`.`model` = 'Institutions' where  `import_mapping`.`model` = 'InstitutionSite';
UPDATE `import_mapping` set `import_mapping`.`lookup_plugin` = 'Area' where  `import_mapping`.`lookup_model` = 'Area';
UPDATE `import_mapping` set `import_mapping`.`lookup_model` = 'Areas' where  `import_mapping`.`lookup_model` = 'Area';
UPDATE `import_mapping` set `import_mapping`.`lookup_plugin` = 'Area' where  `import_mapping`.`lookup_model` = 'AreaAdministrative';
UPDATE `import_mapping` set `import_mapping`.`lookup_model` = 'AreaAdministratives' where  `import_mapping`.`lookup_model` = 'AreaAdministrative';
UPDATE `import_mapping` set `import_mapping`.`lookup_plugin` = 'Institution' where  `import_mapping`.`lookup_model` = 'InstitutionSiteType';
UPDATE `import_mapping` set `import_mapping`.`lookup_alias` = 'InstitutionSiteTypes' where  `import_mapping`.`lookup_model` = 'InstitutionSiteType';
UPDATE `import_mapping` set `import_mapping`.`lookup_model` = 'Types' where  `import_mapping`.`lookup_model` = 'InstitutionSiteType';
UPDATE `import_mapping` set `import_mapping`.`lookup_plugin` = 'Institution' where  `import_mapping`.`lookup_model` = 'InstitutionSiteStatus';
UPDATE `import_mapping` set `import_mapping`.`lookup_alias` = 'InstitutionSiteStatuses' where  `import_mapping`.`lookup_model` = 'InstitutionSiteStatus';
UPDATE `import_mapping` set `import_mapping`.`lookup_model` = 'Statuses' where  `import_mapping`.`lookup_model` = 'InstitutionSiteStatus';
UPDATE `import_mapping` set `import_mapping`.`lookup_plugin` = 'Institution' where  `import_mapping`.`lookup_model` = 'InstitutionSiteSector';
UPDATE `import_mapping` set `import_mapping`.`lookup_alias` = 'InstitutionSiteSectors' where  `import_mapping`.`lookup_model` = 'InstitutionSiteSector';
UPDATE `import_mapping` set `import_mapping`.`lookup_model` = 'Sectors' where  `import_mapping`.`lookup_model` = 'InstitutionSiteSector';
UPDATE `import_mapping` set `import_mapping`.`lookup_plugin` = 'Institution' where  `import_mapping`.`lookup_model` = 'InstitutionSiteProvider';
UPDATE `import_mapping` set `import_mapping`.`lookup_alias` = 'Providers' where  `import_mapping`.`lookup_model` = 'InstitutionSiteProvider';
UPDATE `import_mapping` set `import_mapping`.`lookup_model` = 'Providers' where  `import_mapping`.`lookup_model` = 'InstitutionSiteProvider';
UPDATE `import_mapping` set `import_mapping`.`lookup_plugin` = 'Institution' where  `import_mapping`.`lookup_model` = 'InstitutionSiteOwnership';
UPDATE `import_mapping` set `import_mapping`.`lookup_alias` = 'InstitutionSiteOwnerships' where  `import_mapping`.`lookup_model` = 'InstitutionSiteOwnership';
UPDATE `import_mapping` set `import_mapping`.`lookup_model` = 'Ownerships' where  `import_mapping`.`lookup_model` = 'InstitutionSiteOwnership';
UPDATE `import_mapping` set `import_mapping`.`lookup_plugin` = 'Institution' where  `import_mapping`.`lookup_model` = 'InstitutionSiteLocality';
UPDATE `import_mapping` set `import_mapping`.`lookup_alias` = 'InstitutionSiteLocalities' where  `import_mapping`.`lookup_model` = 'InstitutionSiteLocality';
UPDATE `import_mapping` set `import_mapping`.`lookup_model` = 'Localities' where  `import_mapping`.`lookup_model` = 'InstitutionSiteLocality';
UPDATE `import_mapping` set `import_mapping`.`lookup_plugin` = 'Institution' where  `import_mapping`.`lookup_model` = 'InstitutionSiteGender';
UPDATE `import_mapping` set `import_mapping`.`lookup_alias` = 'InstitutionSiteGenders' where  `import_mapping`.`lookup_model` = 'InstitutionSiteGender';
UPDATE `import_mapping` set `import_mapping`.`lookup_model` = 'Genders' where  `import_mapping`.`lookup_model` = 'InstitutionSiteGender';
