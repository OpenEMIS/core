-- drop description from workflow_actions
ALTER TABLE `workflow_actions` DROP `description`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2540';
