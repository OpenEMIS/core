--
-- 1. navigations
--

SET @orderOfClassesNav := 0;
SELECT `order` INTO @orderOfClassesNav FROM `navigations` WHERE `module` LIKE 'Institution' AND `header` LIKE 'Details' AND `title` LIKE 'Classes';

UPDATE `navigations` SET `order` = `order` + 1 WHERE `order` >= @orderOfClassesNav;

INSERT INTO `navigations` (
`id` ,
`module` ,
`plugin` ,
`controller` ,
`header` ,
`title` ,
`action` ,
`pattern` ,
`attributes` ,
`parent` ,
`is_wizard` ,
`order` ,
`visible` ,
`modified_user_id` ,
`modified` ,
`created_user_id` ,
`created`
)
VALUES (
NULL , 'Institution', NULL , 'InstitutionSites', 'Details', 'Sections', 'InstitutionSiteSection', 'InstitutionSiteSection|InstitutionSiteSection.view', NULL , '3', '0', @orderOfClassesNav, '1', NULL , NULL , '1', '0000-00-00 00:00:00'
);

--
-- 2. security_functions
--

SET @orderOfClassesSecurity := 0;
SELECT `order` INTO @orderOfClassesSecurity FROM `security_functions` WHERE `controller` LIKE 'InstitutionSites' AND `category` LIKE 'Details' AND `name` LIKE 'Classes';

UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` >= @orderOfClassesSecurity;

INSERT INTO `security_functions` (
`id` ,
`name` ,
`controller` ,
`module` ,
`category` ,
`parent_id` ,
`_view` ,
`_edit` ,
`_add` ,
`_delete` ,
`_execute` ,
`order` ,
`visible` ,
`modified_user_id` ,
`modified` ,
`created_user_id` ,
`created`
)
VALUES (
NULL , 'Sections', 'InstitutionSites', 'Institutions', 'Details', '8', 'InstitutionSiteSection|InstitutionSiteSection.index|InstitutionSiteSection.view', '_view:InstitutionSiteSection.edit', '_view:InstitutionSiteSection.add', '_view:InstitutionSiteSection.delete', NULL , @orderOfClassesSecurity, '1', NULL , NULL , '1', '0000-00-00 00:00:00'
);

--
-- 3. set institution_site_shift_id to NULL (institution_site_classes)
--

ALTER TABLE `institution_site_classes` CHANGE `institution_site_shift_id` `institution_site_shift_id` INT( 11 ) NULL ;

--
-- 4. set student_category_id to NULL (institution_site_class_students)
--

ALTER TABLE `institution_site_class_students` CHANGE `student_category_id` `student_category_id` INT( 11 ) NULL ;

--
-- 5. set education_grade_id to NULL (institution_site_class_students)
--

ALTER TABLE `institution_site_class_students` CHANGE `education_grade_id` `education_grade_id` INT( 11 ) NULL ;

--
-- 6. add new column `institution_site_section_id` to table `institution_site_class_students`
--

ALTER TABLE `institution_site_class_students` ADD `institution_site_section_id` INT NOT NULL AFTER `institution_site_class_id` ,
ADD INDEX ( `institution_site_section_id` ) ;

--
-- 7. add new config item
--

INSERT INTO `config_items` (`id`, `name`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES
(NULL, 'max_subjects_per_class', 'Institution Site', 'Max Subjects Per Class', 1, 1, 1, 1, '', '', 1, '0000-00-00 00:00:00');

--
-- 8. changes to `institution_site_student_absences`
--

ALTER TABLE `institution_site_student_absences` ADD `institution_site_section_id` INT NOT NULL AFTER `student_id` ;
ALTER TABLE `institution_site_student_absences` DROP `institution_site_class_id` ;

--
-- 9. new tables
--

CREATE TABLE `institution_site_sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `institution_site_shift_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `school_year_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `institution_site_id` (`institution_site_id`),
  KEY `school_year_id` (`school_year_id`),
  KEY `institution_site_shift_id` (`institution_site_shift_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE `institution_site_section_classes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` int(1) NOT NULL,
  `institution_site_section_id` int(11) NOT NULL,
  `institution_site_class_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `institution_site_section_id` (`institution_site_section_id`),
  KEY `institution_site_class_id` (`institution_site_class_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE `institution_site_section_grades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` int(1) NOT NULL,
  `institution_site_section_id` int(11) NOT NULL,
  `education_grade_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `institution_site_section_id` (`institution_site_section_id`),
  KEY `education_grade_id` (`education_grade_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE `institution_site_section_staff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` int(1) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `institution_site_section_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`),
  KEY `institution_site_section_id` (`institution_site_section_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE `institution_site_section_students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `institution_site_section_id` int(11) NOT NULL,
  `education_grade_id` int(11) NOT NULL,
  `student_category_id` int(11) NOT NULL,
  `status` int(1) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `institution_site_id` (`institution_site_section_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

