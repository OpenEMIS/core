UPDATE config_items SET name = 'Student Admission Age' WHERE config_items.code = 'admission_age_plus';
UPDATE config_items SET label = 'Student Admission Age' WHERE config_items.code = 'admission_age_plus';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1919';
