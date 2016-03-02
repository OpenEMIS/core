-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2608', NOW());

UPDATE labels SET field = 'staff_id' WHERE module = 'InstitutionSections' AND field = 'security_user_id' AND module_name = 'Institutions -> Classes';