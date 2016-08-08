-- 
-- PHPOE-1900 commit.sql
-- 

-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-1900');

UPDATE `institution_sites`
SET `institution_sites`.`date_opened` = CONCAT(`institution_sites`.`year_opened`, '-01-01')
WHERE `institution_sites`.`date_opened` = '0000-00-00' && `institution_sites`.`year_opened` != '0' && `institution_sites`.`year_opened` IS NOT NULL;

-- PHPOE-1902

INSERT INTO `db_patches` VALUES ('PHPOE-1902');

CREATE TABLE `education_programmes_next_programmes` (
  `id` char(36) NOT NULL,
  `education_programme_id` int(11) NOT NULL,
  `next_programme_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `education_programmes_next_programmes` ADD INDEX(`education_programme_id`);
ALTER TABLE `education_programmes_next_programmes` ADD INDEX(`next_programme_id`);

-- PHPOE-1916

INSERT INTO `db_patches` VALUES ('PHPOE-1916');

CREATE TABLE `z_1916_academic_periods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `current` char(1) NOT NULL DEFAULT '0',
  `available` char(1) NOT NULL DEFAULT '1',
  `visible` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;

INSERT INTO `z_1916_academic_periods`
SELECT `id`, `current`, `available`, `visible`
FROM `academic_periods`;

ALTER TABLE `academic_periods` 
CHANGE COLUMN `current` `current` INT(1) NOT NULL DEFAULT '0',
CHANGE COLUMN `available` `editable` INT(1) NOT NULL DEFAULT '1' ,
ADD INDEX `current` (`current`),
ADD INDEX `visible` (`visible`),
ADD INDEX `editable` (`editable`),
ADD INDEX `parent_id` (`parent_id`);

Update `academic_periods` SET `editable` = 1, `visible` = 1 WHERE `current` = 1; 

-- PHPOE-1933

-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-1933');

-- security_functions
UPDATE `security_functions` SET `_view` = 'Surveys.index|Surveys.view|NewSurveys.index|NewSurveys.view', `_add` = NULL, `_edit` = 'Surveys.edit|NewSurveys.edit', `_delete` = 'Surveys.remove|NewSurveys.remove', `_execute` = NULL
WHERE `id` = 1024;

UPDATE `security_functions` SET `_view` = 'Surveys.index|Surveys.view|CompletedSurveys.index|CompletedSurveys.view', `_add` = NULL, `_edit` = NULL, `_delete` = 'Surveys.remove|CompletedSurveys.remove', `_execute` = NULL
WHERE `id` = 1025;

-- PHPOE-1982

-- db_patches
CREATE TABLE IF NOT EXISTS `db_patches` (
  `issue` varchar(15) NOT NULL,
  PRIMARY KEY (`issue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `db_patches` VALUES ('PHPOE-1982');

-- institution_site_assessments
ALTER TABLE `institution_site_assessments` CHANGE `status` `status` INT(1) NOT NULL DEFAULT '0' COMMENT '-1 -> Expired, 0 -> New, 1 -> Draft, 2 -> Completed';

-- security_functions
UPDATE `security_functions` SET `_view` = 'Assessments.index|Results.index', `_edit` = 'Results.indexEdit', `_add` = NULL, `_delete` = 'Assessments.remove', `_execute` = NULL WHERE `id` = 1015;

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `created_user_id`, `created`) VALUES (uuid(), 'Results', 'openemis_no', 'Institutions -> Students -> Results', 'OpenEMIS ID', 1, NOW());

UPDATE `config_items` SET `value` = '3.2.1' WHERE `code` = 'db_version';
