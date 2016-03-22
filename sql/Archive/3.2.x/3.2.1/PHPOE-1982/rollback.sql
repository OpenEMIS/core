-- institution_site_assessments
ALTER TABLE `institution_site_assessments` CHANGE `status` `status` INT(1) NOT NULL DEFAULT '0' COMMENT '0 -> New, 1 -> Draft, 2 -> Completed';

-- security_functions
UPDATE `security_functions` SET `_view` = 'Assessments.index|Results.index', `_edit` = 'Results.edit', `_add` = 'Results.add', `_delete` = NULL, `_execute` = NULL WHERE `id` = 1015;

-- labels
DELETE FROM `labels` WHERE `module` = 'Results' AND `field` = 'openemis_no';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1982';
