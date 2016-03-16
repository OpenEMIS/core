-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2540', NOW());

-- add description to workflow_actions
ALTER TABLE `workflow_actions` ADD `description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `name`;
