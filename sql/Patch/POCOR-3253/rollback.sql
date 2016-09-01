-- Restore tables
DROP TABLE IF EXISTS `institution_surveys`;
RENAME TABLE `z_3253_institution_surveys` TO `institution_surveys`;

DROP TABLE IF EXISTS `staff_leaves`;
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

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3253';
