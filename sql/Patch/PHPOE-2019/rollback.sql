-- institution student dropout
DROP TABLE IF EXISTS `institution_student_dropout`;

-- dummy data for the student dropout reasons
DELETE FROM `field_option_values`
WHERE `field_option_values`.`id` = (SELECT `field_options`.`id` FROM `field_options` WHERE `code` = 'StudentDropoutReasons') AND `field_option_values`.`name`='Relocation';

-- field_options
DELETE FROM `field_options` WHERE `plugin`='Students' AND `code`='StudentDropoutReasons';

-- student_statuses
DELETE FROM `student_statuses` WHERE `code` = 'PENDING_DROPOUT';

-- security_functions
DELETE FROM `security_functions` WHERE `id`=1030;
DELETE FROM `security_functions` WHERE `id`=1031;

-- label
DELETE FROM `labels` WHERE `module`='StudentDropout' and `field`='created';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2019';