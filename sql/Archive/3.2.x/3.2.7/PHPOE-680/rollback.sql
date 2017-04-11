-- labels
DELETE FROM `labels` WHERE `module` = 'InstitutionRubrics' AND `field` = 'institution_site_id';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-680';