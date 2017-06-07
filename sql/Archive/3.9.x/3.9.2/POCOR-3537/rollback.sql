-- labels
DELETE FROM `labels` WHERE `module` = 'RubricTemplates' AND `field` = 'name';
DELETE FROM `labels` WHERE `module` = 'RubricSections' AND `field` = 'name';
DELETE FROM `labels` WHERE `module` = 'RubricCriterias' AND `field` = 'name';
DELETE FROM `labels` WHERE `module` = 'RubricTemplateOptions' AND `field` = 'name';

-- db_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3537';
