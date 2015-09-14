DELETE FROM `labels` WHERE `module` = 'Institutions' AND `field` = 'area_administrative_id';

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1508';