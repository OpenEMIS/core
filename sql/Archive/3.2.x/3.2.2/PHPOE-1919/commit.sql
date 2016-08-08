INSERT INTO `db_patches` VALUES ('PHPOE-1919');

UPDATE config_items SET name = 'Admission Age Plus' WHERE config_items.code = 'admission_age_plus';
UPDATE config_items SET label = 'Admission Age Plus' WHERE config_items.code = 'admission_age_plus';