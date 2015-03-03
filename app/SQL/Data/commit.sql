DROP TABLE IF EXISTS `institution_site_activities`;
CREATE TABLE IF NOT EXISTS `institution_site_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `model` varchar(200) NOT NULL,
  `model_reference` int(11) NOT NULL,
  `field` varchar(200) NOT NULL,
  `old_value` varchar(255) NOT NULL,
  `new_value` varchar(255) NOT NULL,
  `operation` varchar(10) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `institution_site_id` (`institution_site_id`),
  KEY `model_reference` (`model_reference`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

