UPDATE `security_functions` SET `_execute`=NULL WHERE `id`='1015';

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1420';