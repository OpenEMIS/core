-- Revert and restore table
DROP TABLE IF EXISTS `workflow_transitions`;
RENAME TABLE `z_2250_workflow_transitions` TO `workflow_transitions`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2250';

-- security_functions
DELETE FROM `security_functions` WHERE `id` = 1025;

INSERT INTO `security_functions` SELECT * FROM `z_2298_security_functions`;

DROP TABLE `z_2298_security_functions`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2298';
-- security_functions
UPDATE `security_functions` SET `_execute`=NULL WHERE `id`=1014;
UPDATE `security_functions` SET `_execute`=NULL WHERE `id`=1018;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2310';
UPDATE config_items SET value = '3.3.7' WHERE code = 'db_version';
