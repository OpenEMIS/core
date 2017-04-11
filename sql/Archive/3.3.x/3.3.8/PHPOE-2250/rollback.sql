-- Revert and restore table
DROP TABLE IF EXISTS `workflow_transitions`;
RENAME TABLE `z_2250_workflow_transitions` TO `workflow_transitions`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2250';
