-- security_functions
UPDATE `security_functions` SET `_execute`=null WHERE `id`=1025;
UPDATE `security_functions` SET `_execute`=null WHERE `id`=1029;
DELETE FROM `security_functions` WHERE `id`=6003;
DELETE FROM `security_functions` WHERE `id`=6004;
DELETE FROM `security_functions` WHERE `id`=6006;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2281';