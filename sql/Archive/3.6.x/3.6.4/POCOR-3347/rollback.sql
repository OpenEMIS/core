-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3347';

DELETE FROM `translations`
WHERE `en` = 'There are no shifts configured for the selected academic period';