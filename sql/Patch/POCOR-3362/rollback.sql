-- labels
DELETE FROM `labels` WHERE `module` = 'StudentTransfer' AND `field` = 'education_grade_id';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3362';
