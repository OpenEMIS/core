-- security_functions
UPDATE `security_functions` SET `name` = 'Results' WHERE `id` IN (1015,2016,7015);


-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3583';
