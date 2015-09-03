-- field_options
DELETE FROM `field_options` WHERE `plugin`='Students' AND `code`='StudentDropoutReasons';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1573';