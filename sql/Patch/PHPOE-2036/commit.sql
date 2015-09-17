INSERT INTO `db_patches` VALUES ('PHPOE-2036');

UPDATE config_items SET default_value = 1 WHERE code = 'institution_area_level_id';