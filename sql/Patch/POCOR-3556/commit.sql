-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3556', NOW());

-- training_session_trainers
CREATE TABLE `z_3556_training_session_trainers` LIKE `training_session_trainers`;
INSERT `z_3556_training_session_trainers` SELECT * FROM `training_session_trainers`;

ALTER TABLE `training_session_trainers` DROP `type`;
