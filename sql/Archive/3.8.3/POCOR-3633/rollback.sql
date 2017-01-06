
-- security_function
UPDATE `security_functions` SET `_edit`='StudentUser.edit' WHERE `id`='2000';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3633';
