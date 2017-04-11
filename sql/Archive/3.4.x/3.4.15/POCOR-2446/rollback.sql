-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-2446';

UPDATE custom_field_types SET visible = 0 WHERE code = 'DATE';
UPDATE custom_field_types SET visible = 0 WHERE code = 'TIME';