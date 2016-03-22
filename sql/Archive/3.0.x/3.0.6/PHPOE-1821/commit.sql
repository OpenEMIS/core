CREATE TABLE IF NOT EXISTS `db_patches` (
  `issue` varchar(15) NOT NULL,
  PRIMARY KEY (`issue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `db_patches` VALUES ('PHPOE-1821');

UPDATE `security_functions` SET
`_view` = 'AllClasses.index|AllClasses.view|Sections.index|Sections.view',
`_edit` = 'AllClasses.edit|Sections.edit',
`parent_id` = 1000
WHERE `id` = 1006;

UPDATE `security_functions` SET
`_view` = 'Sections.index|Sections.view',
`_edit` = 'Sections.edit'
WHERE `id` = 1007;
