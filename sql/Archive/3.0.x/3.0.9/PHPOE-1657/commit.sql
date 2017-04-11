-- db_patches
CREATE TABLE IF NOT EXISTS `db_patches` (
  `issue` varchar(15) NOT NULL,
  PRIMARY KEY (`issue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `db_patches` VALUES ('PHPOE-1657');

-- security_functions
UPDATE `security_functions` SET `_view` = 'Surveys.index|Surveys.view', `_add` = NULL, `_edit` = 'Surveys.edit', `_delete` = 'Surveys.remove', `_execute` = NULL
WHERE `controller` = 'Institutions' AND `module` = 'Institutions' AND `category` = 'Surveys' AND `name` = 'New';

UPDATE `security_functions` SET `_view` = 'Surveys.index|Surveys.view', `_add` = NULL, `_edit` = NULL, `_delete` = 'Surveys.remove', `_execute` = NULL
WHERE `controller` = 'Institutions' AND `module` = 'Institutions' AND `category` = 'Surveys' AND `name` = 'Completed';
