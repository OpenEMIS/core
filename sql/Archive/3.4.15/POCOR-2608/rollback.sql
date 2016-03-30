UPDATE labels SET field = 'security_user_id' WHERE module = 'InstitutionSections' AND field = 'staff_id' AND module_name = 'Institutions -> Classes';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2608';