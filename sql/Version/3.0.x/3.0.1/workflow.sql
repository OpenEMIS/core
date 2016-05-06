-- 22nd July 2015

RENAME TABLE `workflow_step_roles` TO `workflow_steps_roles`;
RENAME TABLE `workflow_submodels` TO `workflows_filters`;

ALTER TABLE `workflow_models` CHANGE `submodel` `filter` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `workflows_filters` CHANGE `submodel_reference` `filter_id` INT(11) NOT NULL;
