-- POCOR-3362
-- labels
DELETE FROM `labels` WHERE `module` = 'StudentTransfer' AND `field` = 'education_grade_id';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3362';


-- 3.6.2
UPDATE config_items SET value = '3.6.2' WHERE code = 'db_version';
