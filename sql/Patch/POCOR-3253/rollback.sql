-- Restore tables
DROP TABLE IF EXISTS `workflow_models`;
RENAME TABLE `z_3253_workflow_models` TO `workflow_models`;

DROP TABLE IF EXISTS `workflow_steps`;
RENAME TABLE `z_3253_workflow_steps` TO `workflow_steps`;

DROP TABLE IF EXISTS `workflow_actions`;
RENAME TABLE `z_3253_workflow_actions` TO `workflow_actions`;

RENAME TABLE `z_3253_workflow_records` TO `workflow_records`;

DROP TABLE IF EXISTS `workflow_transitions`;
RENAME TABLE `z_3253_workflow_transitions` TO `workflow_transitions`;

DROP TABLE IF EXISTS `institution_surveys`;
RENAME TABLE `z_3253_institution_surveys` TO `institution_surveys`;

DROP TABLE IF EXISTS `institution_staff_leave`;
RENAME TABLE `z_3253_staff_leaves` TO `staff_leaves`;

DROP TABLE IF EXISTS `institution_positions`;
RENAME TABLE `z_3253_institution_positions` TO `institution_positions`;

DROP TABLE IF EXISTS `institution_staff_position_profiles`;
RENAME TABLE `z_3253_institution_staff_position_profiles` TO `institution_staff_position_profiles`;

DROP TABLE IF EXISTS `training_courses`;
RENAME TABLE `z_3253_training_courses` TO `training_courses`;

DROP TABLE IF EXISTS `training_sessions`;
RENAME TABLE `z_3253_training_sessions` TO `training_sessions`;

DROP TABLE IF EXISTS `training_session_results`;
RENAME TABLE `z_3253_training_session_results` TO `training_session_results`;

DROP TABLE IF EXISTS `staff_training_needs`;
RENAME TABLE `z_3253_staff_training_needs` TO `staff_training_needs`;

-- security_functions
DELETE FROM `security_functions` WHERE `id` = 5049;

UPDATE `security_functions` SET `controller` = 'Staff', `_view` = 'Leave.index|Leave.view', `_edit` = 'Leave.edit', `_add` = 'Leave.add', `_delete` = 'Leave.remove', `_execute` = 'Leave.download' WHERE `id` = 3016;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3253';
