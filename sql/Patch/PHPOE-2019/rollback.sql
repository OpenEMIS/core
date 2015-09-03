-- dummy data for the student dropout reasons
DELETE FROM `field_option_values`
WHERE `field_option_values`.`id` = (SELECT `field_options`.`id` FROM `field_options` WHERE `code` = 'StudentDropoutReasons') AND `field_option_values`.`name`='Relocation';

-- field_options
DELETE FROM `field_options` WHERE `plugin`='Students' AND `code`='StudentDropoutReasons';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2019';