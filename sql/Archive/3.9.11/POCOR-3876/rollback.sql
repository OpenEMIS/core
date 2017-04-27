-- labels
DELETE FROM `labels`
WHERE `id` = 'b7b9aad6-1ff1-11e7-a840-525400b263eb';

-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3876';
