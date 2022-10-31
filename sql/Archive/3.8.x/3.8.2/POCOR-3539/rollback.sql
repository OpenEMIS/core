-- translations
DELETE FROM `translations` WHERE `en` = 'Student has been transferred to';
DELETE FROM `translations` WHERE `en` = 'after registration';

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3539';
