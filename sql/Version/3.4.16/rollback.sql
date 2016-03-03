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


-- POCOR-2603
DELETE FROM labels WHERE module = 'Accounts' AND field = 'password';
DELETE FROM labels WHERE module = 'Accounts' AND field = 'retype_password';
DELETE FROM labels WHERE module = 'StudentAccount' AND field = 'password';
DELETE FROM labels WHERE module = 'StudentAccount' AND field = 'retype_password';
DELETE FROM labels WHERE module = 'StaffAccount' AND field = 'password';
DELETE FROM labels WHERE module = 'StaffAccount' AND field = 'retype_password';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2603';


-- POCOR-2658
-- labels
UPDATE `labels` SET `field_name` = 'Area (Administrative)' WHERE `module` = 'Institutions' AND `field` = 'area_administrative_id';
UPDATE `labels` SET `field_name` = 'Area (Education)' WHERE `module` = 'Institutions' AND `field` = 'area_id';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2658';


-- 3.4.15
-- db_version
UPDATE config_items SET value = '3.4.15' WHERE code = 'db_version';
