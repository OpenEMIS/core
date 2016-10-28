-- POCOR-3492
-- security_functions
UPDATE `security_functions` SET `_add`='StudentUser.add' WHERE `id`='1043';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3492';


-- 3.7.1.1
UPDATE config_items SET value = '3.7.1.1' WHERE code = 'db_version';
