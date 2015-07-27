Drop Table IF EXISTS `institution_site_section_students`;

CREATE TABLE `institution_site_section_students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `security_user_id` int(11) NOT NULL,
  `institution_site_section_id` int(11) NOT NULL,
  `education_grade_id` int(11) NOT NULL,
  `student_category_id` int(11) NOT NULL,
  `status` int(1) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `institution_site_id` (`institution_site_section_id`),
  KEY `security_user_id` (`security_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3334 DEFAULT CHARSET=utf8;

INSERT INTO `institution_site_section_students` 
SELECT *
FROM `z_1716_institution_site_section_students`;