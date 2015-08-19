-- labels
DELETE FROM `labels` WHERE `module` = 'StudentPromotion';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1857';
