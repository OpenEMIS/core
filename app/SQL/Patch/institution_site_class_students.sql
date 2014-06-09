
DROP TABLE IF EXISTS `institution_site_class_students`;
CREATE TABLE IF NOT EXISTS `institution_site_class_students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` int(1) NOT NULL,
  `student_id` int(11) NOT NULL,
  `student_category_id` int(11) NOT NULL,
  `institution_site_class_id` int(11) NOT NULL,
  `education_grade_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `student_category_id` (`student_category_id`),
  KEY `institution_site_class_id` (`institution_site_class_id`),
  KEY `education_grade_id` (`education_grade_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `institution_site_class_subjects` ADD `status` INT( 1 ) NOT NULL ;
