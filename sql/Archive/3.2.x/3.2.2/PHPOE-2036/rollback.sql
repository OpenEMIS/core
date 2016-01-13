UPDATE config_items SET default_value = 3 WHERE code = 'institution_area_level_id';

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2036';