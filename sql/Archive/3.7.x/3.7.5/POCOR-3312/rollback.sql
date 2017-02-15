-- config items
DELETE FROM `translations` WHERE `en` = 'There are no shifts configured for the selected academic period, will be using system configuration timing';


-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3312';
