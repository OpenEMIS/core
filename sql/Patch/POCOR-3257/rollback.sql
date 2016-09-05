-- code here
DELETE FROM `config_items` WHERE `code` = 'area_api';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3257';


