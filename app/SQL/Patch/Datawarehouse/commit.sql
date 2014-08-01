DROP TABLE IF EXISTS `datawarehouse_indicators`;
CREATE TABLE IF NOT EXISTS `datawarehouse_indicators` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `code` varchar(60) NOT NULL,
  `description` text,
  `editable` int(1) NOT NULL DEFAULT 1,
  `enabled` int(1) NOT NULL DEFAULT '1',
  `type` varchar(30) NOT NULL,
  `unit_id` int(5) NOT NULL,
  `field_id` int(5) NOT NULL,
  `denominator` int(5),
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `field_id` (`field_id`),
  KEY `unit_id` (`unit_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `datawarehouse_units`;
CREATE TABLE IF NOT EXISTS `datawarehouse_units` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `datawarehouse_conditions`;
CREATE TABLE IF NOT EXISTS `datawarehouse_conditions` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `operator` varchar(20) NOT NULL,
  `value` varchar(50) NOT NULL,
  `dimension_id` int(5) NOT NULL,
  `indicator_id` int(5) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `dimension_id` (`dimension_id`),
  KEY `indicator_id` (`indicator_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `datawarehouse_modules`;
CREATE TABLE IF NOT EXISTS `datawarehouse_modules` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `model` varchar(100) NOT NULL,
  `joins` text,
  `enabled` int(1) NOT NULL DEFAULT '1',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `datawarehouse_fields`;
CREATE TABLE IF NOT EXISTS `datawarehouse_fields` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `type` varchar(20) NOT NULL,
  `module_id` int(5) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `module_id` (`module_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `datawarehouse_dimensions`;
CREATE TABLE IF NOT EXISTS `datawarehouse_dimensions` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `field` varchar(50) NOT NULL,
  `model` varchar(50),
  `joins` text, 
  `module_id` int(5) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `module_id` (`module_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


