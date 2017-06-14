-- training_session_trainers
DROP TABLE IF EXISTS `training_session_trainers`;
RENAME TABLE `z_3556_training_session_trainers` TO `training_session_trainers`;

-- db_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3556';
