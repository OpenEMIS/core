-- custom_field_types
UPDATE `custom_field_types` SET `is_mandatory` = 0 WHERE `code` = 'DROPDOWN';

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-2634';
