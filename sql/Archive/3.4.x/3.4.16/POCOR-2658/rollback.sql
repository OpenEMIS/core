-- labels

UPDATE `labels` SET `field_name` = 'Area (Administrative)' WHERE `module` = 'Institutions' AND `field` = 'area_administrative_id';
UPDATE `labels` SET `field_name` = 'Area (Education)' WHERE `module` = 'Institutions' AND `field` = 'area_id';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2658';