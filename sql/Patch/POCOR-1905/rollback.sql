DELETE FROM config_items WHERE code = 'password_min_length';
DELETE FROM config_items WHERE code = 'password_has_uppercase';
DELETE FROM config_items WHERE code = 'password_has_lowercase';
DELETE FROM config_items WHERE code = 'password_has_number';
DELETE FROM config_items WHERE code = 'password_has_non_alpha';

DELETE FROM `db_patches` WHERE `issue` = 'POCOR-1905';	