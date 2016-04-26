-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2446', NOW());

UPDATE custom_field_types SET visible = 1 WHERE code = 'DATE';
UPDATE custom_field_types SET visible = 1 WHERE code = 'TIME';