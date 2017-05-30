-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-2634', NOW());

-- custom_field_types
UPDATE `custom_field_types` SET `is_mandatory` = 1 WHERE `code` = 'DROPDOWN';
