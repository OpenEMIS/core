DROP TABLE IF EXISTS `field_options`;
CREATE TABLE IF NOT EXISTS `field_options` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(50) NOT NULL,
  `parent` varchar(50) NULL,
  `params` text NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT 1,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `field_option_values`;
CREATE TABLE IF NOT EXISTS `field_option_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT 1,
  `editable` int(1) NOT NULL DEFAULT 1,
  `international_code` varchar(10) NULL,
  `national_code` varchar(10) NULL,
  `field_option_id` int(5) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `field_option_id` (`field_option_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `institution_site_programmes` ADD `status` INT( 1 ) NOT NULL AFTER `id` ;

--
-- Update table `navigations` to remove institution from navigation
--

DELETE 
FROM `navigations`
WHERE `module` LIKE 'Institution'
AND `controller` LIKE 'Institutions'
AND `header` LIKE 'GENERAL';

DELETE 
FROM `navigations`
WHERE `module` LIKE 'Institution'
AND `controller` LIKE 'Institutions'
AND `header` LIKE 'INSTITUTION SITE';

DELETE 
FROM `navigations`
WHERE `module` LIKE 'Institution'
AND `controller` LIKE 'InstitutionSites'
AND `header` LIKE 'INSTITUTION SITE';

UPDATE `navigations` SET `controller` = 'InstitutionSites' 
WHERE `module` LIKE 'Institution'
AND `controller` LIKE 'Institutions'
AND `title` LIKE 'List of Institutions';
 
UPDATE `navigations` SET `controller` = 'InstitutionSites' 
WHERE `module` LIKE 'Institution'
AND `controller` LIKE 'Institutions'
AND `title` LIKE 'Add new Institution';