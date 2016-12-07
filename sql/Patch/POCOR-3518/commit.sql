INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3518', NOW());

CREATE TABLE `z_3518_config_items` LIKE `config_items`;

INSERT INTO `z_3518_config_items`
SELECT * FROM `config_items` WHERE `code` = 'student_prefix' OR `code` = 'staff_prefix' OR `code` = 'guardian_prefix';

DELETE FROM `config_items` WHERE `code` = 'student_prefix' OR `code` = 'staff_prefix' OR `code` = 'guardian_prefix';

INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('1003', 'OpenEMIS ID Prefix', 'openemis_id_prefix', 'Auto Generated OpenEMIS ID', 'OpenEMIS ID Prefix', ',0', ',0', '1', '1', '', '', NULL, NULL, '1', NOW());
