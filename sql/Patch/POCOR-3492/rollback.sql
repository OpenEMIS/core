-- security_functions
UPDATE `security_functions` SET `_add`='StudentUser.add' WHERE `id`='1043';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3492';
