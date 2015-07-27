Drop Table IF EXISTS `z_1716_institution_site_section_students`;

CREATE TABLE `z_1716_Institution_Site_Section_Students` (
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

INSERT INTO `z_1716_institution_site_section_students` 
SELECT *
FROM `institution_site_section_students`;

UPDATE institution_site_section_students
SET student_category_id = (
	SELECT field_option_values.id
    FROM field_option_values, field_options
	WHERE field_option_values.field_option_id = field_options.id
		AND field_options.code = 'StudentCategories'
        AND field_option_values.name = 'Promoted or New Enrolment'
)
WHERE student_category_id NOT IN (
	SELECT field_option_values.id
	FROM field_option_values, field_options
	WHERE field_option_values.field_option_id = field_options.id 
		AND field_options.code='StudentCategories'
);