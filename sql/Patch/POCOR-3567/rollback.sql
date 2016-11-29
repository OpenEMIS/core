-- restore table
DROP TABLE IF EXISTS `import_mapping`;
RENAME TABLE `z_3567_import_mapping` TO `import_mapping`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3567';
