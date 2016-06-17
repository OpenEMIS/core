-- institution_infrastructures
-- ALTER TABLE `institution_infrastructures` DROP `parent_id`;
-- ALTER TABLE `institution_infrastructures` DROP `lft`;
-- ALTER TABLE `institution_infrastructures` DROP `rght`;

-- revert move out infrastructure_ownerships from field_options
DROP TABLE IF EXISTS `infrastructure_ownerships`;
UPDATE `field_options` SET `params` = NULL WHERE `code` = 'InfrastructureOwnerships';
UPDATE `field_option_values` SET `visible` = 1 WHERE `field_option_id` = (SELECT `id` FROM `field_options` WHERE `code` = 'InfrastructureOwnerships');

-- revert move out infrastructure_conditions from field_options
DROP TABLE IF EXISTS `infrastructure_conditions`;
UPDATE `field_options` SET `params` = NULL WHERE `code` = 'InfrastructureConditions';
UPDATE `field_option_values` SET `visible` = 1 WHERE `field_option_id` = (SELECT `id` FROM `field_options` WHERE `code` = 'InfrastructureConditions');

-- Restore table
DROP TABLE `institution_infrastructures`;
RENAME TABLE `z_2392_institution_infrastructures` TO `institution_infrastructures`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2392';
