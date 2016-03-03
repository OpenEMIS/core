-- POCOR-1905
DELETE FROM config_items WHERE code = 'password_min_length';
DELETE FROM config_items WHERE code = 'password_has_uppercase';
DELETE FROM config_items WHERE code = 'password_has_number';
DELETE FROM config_items WHERE code = 'password_has_non_alpha';

DELETE FROM `db_patches` WHERE `issue` = 'POCOR-1905';


-- POCOR-2208
UPDATE labels SET field_name = 'Removable' WHERE module = 'WorkflowSteps' AND module_name = 'Workflow -> Steps' AND field_name = 'Deletable';

-- db_patches
DELETE FROM `db_patches` WHERE  `issue` = 'POCOR-2208';


-- 3.4.15
-- db_version
UPDATE config_items SET value = '3.4.15' WHERE code = 'db_version';
