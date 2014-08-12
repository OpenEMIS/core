DROP TABLE IF EXISTS `institution_site_class_staff`;
CREATE TABLE IF NOT EXISTS `institution_site_class_staff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` int(1) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `institution_site_class_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`),
  KEY `institution_site_class_id` (`institution_site_class_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
