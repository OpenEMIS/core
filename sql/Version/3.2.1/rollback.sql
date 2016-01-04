-- 
-- PHPOE-1900 rollback.sql
-- 

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1900';


DROP TABLE IF EXISTS education_programmes_next_programmes;


DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1902';

ALTER TABLE `academic_periods` 
CHANGE COLUMN `editable` `available` CHAR(1) NOT NULL DEFAULT '1',
CHANGE COLUMN `visible` `visible` CHAR(1) NOT NULL DEFAULT '1',
DROP INDEX `parent_id` ,
DROP INDEX `editable` ,
DROP INDEX `visible` ,
DROP INDEX `current` ;

UPDATE `academic_periods`
LEFT JOIN `z_1916_academic_periods` ON `academic_periods`.`id` = `z_1916_academic_periods`.`id`
  SET `academic_periods`.`current` = `z_1916_academic_periods`.`current`,
    `academic_periods`.`available` = `z_1916_academic_periods`.`available`,
    `academic_periods`.`visible` = `z_1916_academic_periods`.`visible`
  WHERE `academic_periods`.`id` = `z_1916_academic_periods`.`id`;
DROP TABLE `z_1916_academic_periods`;

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1916';

-- security_functions
UPDATE `security_functions` SET `_view` = 'Surveys.index|Surveys.view', `_add` = NULL, `_edit` = 'Surveys.edit', `_delete` = 'Surveys.remove', `_execute` = NULL
WHERE `id` = 1024;

UPDATE `security_functions` SET `_view` = 'Surveys.index|Surveys.view', `_add` = NULL, `_edit` = NULL, `_delete` = 'Surveys.remove', `_execute` = NULL
WHERE `id` = 1025;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1933';


-- institution_site_assessments
ALTER TABLE `institution_site_assessments` CHANGE `status` `status` INT(1) NOT NULL DEFAULT '0' COMMENT '0 -> New, 1 -> Draft, 2 -> Completed';

-- security_functions
UPDATE `security_functions` SET `_view` = 'Assessments.index|Results.index', `_edit` = 'Results.edit', `_add` = 'Results.add', `_delete` = NULL, `_execute` = NULL WHERE `id` = 1015;

-- labels
DELETE FROM `labels` WHERE `module` = 'Results' AND `field` = 'openemis_no';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1982';

UPDATE `config_items` SET `value` = '3.1.5' WHERE `code` = 'db_version';
