-- db_rollback
DROP TABLE IF EXISTS `user_special_needs`;

ALTER TABLE `z_2885_user_special_needs` 
RENAME TO  `user_special_needs`;

-- db_rollback
DROP TABLE IF EXISTS `field_options`;

ALTER TABLE `z_2885_field_options` 
RENAME TO  `field_options`;


DROP TABLE IF EXISTS `special_need_type_difficulties`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2885';