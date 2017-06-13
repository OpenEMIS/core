-- institution_statuses
DROP TABLE IF EXISTS `institution_statuses`;
RENAME TABLE `z_3983_institution_statuses` TO `institution_statuses`;

-- institutions
DROP TABLE IF EXISTS `institutions`;
RENAME TABLE `z_3983_institutions` TO `institutions`;

-- import_mapping
DROP TABLE IF EXISTS `import_mapping`;
RENAME TABLE `z_3983_import_mapping` TO `import_mapping`;


-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3983';

