
-- labels
UPDATE `labels` SET `field_name`='Location' WHERE `module` = 'InstitutionShifts' AND `field` = 'location_institution_id';

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1903';
UPDATE config_items SET value = '3.4.4' WHERE code = 'db_version';
