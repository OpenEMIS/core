-- labels
UPDATE `labels` SET `field_name`='Location' WHERE `module` = 'InstitutionShifts' AND `field` = 'location_institution_id';

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1903';