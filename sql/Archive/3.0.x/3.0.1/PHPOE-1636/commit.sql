ALTER TABLE `student_guardians` CHANGE `security_user_id` `student_user_id` INT(11) NOT NULL COMMENT 'is security_user.id';
ALTER TABLE `student_guardians` CHANGE `guardian_id` `guardian_user_id` INT(11) NOT NULL COMMENT 'is security_user.id';
ALTER TABLE `student_guardians` ADD INDEX( `student_user_id`, `guardian_user_id`);

INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`) VALUES (NULL, 'Guardian Prefix', 'guardian_prefix', 'Auto Generated OpenEMIS ID', 'Guardian Prefix', ',0', ',0', '1', '1', '', '');

CREATE TABLE guardian_activities LIKE student_activities;