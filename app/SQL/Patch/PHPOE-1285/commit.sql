--
-- 1. Navigations
--

UPDATE `navigations` SET `plugin` = 'Quality', `controller` = 'QualityRubrics', `action` = 'index' , `pattern` = 'index|RubricTemplate|RubricTemplateOption|RubricSection|RubricCriteria' WHERE `module` = 'Administration' AND `controller` = 'Quality' AND `header` = 'Quality' AND `title` = 'Rubrics';
UPDATE `navigations` SET `plugin` = 'Quality', `controller` = 'QualityStatuses', `action` = 'index' , `pattern` = 'index|view|edit|add|delete' WHERE `module` = 'Administration' AND `controller` = 'Quality' AND `header` = 'Quality' AND `title` = 'Status';

UPDATE `navigations` SET `plugin` = 'Quality', `controller` = 'InstitutionSites', `action` = 'InstitutionSiteQualityRubric' , `pattern` = 'InstitutionSiteQualityRubric' WHERE `module` = 'Institution' AND `controller` = 'Quality' AND `header` = 'Quality' AND `title` = 'Rubrics';
UPDATE `navigations` SET `plugin` = 'Quality', `controller` = 'InstitutionSites', `action` = 'InstitutionSiteQualityVisit' , `pattern` = 'InstitutionSiteQualityVisit' WHERE `module` = 'Institution' AND `controller` = 'Quality' AND `header` = 'Quality' AND `title` = 'Visits';

--
-- 2. Security Functions
--

UPDATE `security_functions` SET `controller` = 'QualityRubrics', `_view` = 'index|view', `_edit` = '_view:edit' , `_add` = '_view:add', `_delete` = '_view:delete' WHERE `controller` = 'Quality' AND `module` = 'Administration' AND `category` = 'Quality' AND `name` = 'Rubrics';
UPDATE `security_functions` SET `controller` = 'QualityStatuses', `_view` = 'index|view', `_edit` = '_view:edit' , `_add` = '_view:add', `_delete` = '_view:delete' WHERE `controller` = 'Quality' AND `module` = 'Administration' AND `category` = 'Quality' AND `name` = 'Status';

UPDATE `security_functions` SET `controller` = 'InstitutionSites', `_view` = 'InstitutionSiteQualityRubric|InstitutionSiteQualityRubric.index|InstitutionSiteQualityRubric.view', `_edit` = '_view:InstitutionSiteQualityRubric.edit' , `_add` = '_view:InstitutionSiteQualityRubric.add', `_delete` = '_view:InstitutionSiteQualityRubric.remove', `_execute` = '_view:InstitutionSiteQualityRubric.excel' WHERE `controller` = 'Quality' AND `module` = 'Institutions' AND `category` = 'Quality' AND `name` = 'Rubrics';
UPDATE `security_functions` SET `controller` = 'InstitutionSites', `_view` = 'InstitutionSiteQualityVisit|InstitutionSiteQualityVisit.index|InstitutionSiteQualityVisit.view', `_edit` = '_view:InstitutionSiteQualityVisit.edit' , `_add` = '_view:InstitutionSiteQualityVisit.add', `_delete` = '_view:InstitutionSiteQualityVisit.remove', `_execute` = '_view:InstitutionSiteQualityVisit.excel' WHERE `controller` = 'Quality' AND `module` = 'Institutions' AND `category` = 'Quality' AND `name` = 'Visits';

--
-- 3. Backup tables
--

RENAME TABLE rubrics_templates TO z_1285_rubrics_templates;
RENAME TABLE rubrics_template_answers TO z_1285_rubrics_template_answers;
RENAME TABLE rubrics_template_column_infos TO z_1285_rubrics_template_column_infos;
RENAME TABLE rubrics_template_grades TO z_1285_rubrics_template_grades;
RENAME TABLE rubrics_template_headers TO z_1285_rubrics_template_headers;
RENAME TABLE rubrics_template_items TO z_1285_rubrics_template_items;
RENAME TABLE rubrics_template_subheaders TO z_1285_rubrics_template_subheaders;
RENAME TABLE quality_statuses TO z_1285_quality_statuses;

RENAME TABLE quality_institution_rubrics TO z_1285_quality_institution_rubrics;
RENAME TABLE quality_institution_rubrics_answers TO z_1285_quality_institution_rubrics_answers;
RENAME TABLE quality_institution_visits TO z_1285_quality_institution_visits;
RENAME TABLE quality_institution_visit_attachments TO z_1285_quality_institution_visit_attachments;

--
-- 4. New table - rubric_templates
--

DROP TABLE IF EXISTS `rubric_templates`;
CREATE TABLE IF NOT EXISTS `rubric_templates` (
`id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `weighting_type` int(1) NOT NULL COMMENT '1 -> point, 2 -> percent',
  `pass_mark` int(5) NOT NULL DEFAULT '0',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `rubric_templates`
 ADD PRIMARY KEY (`id`);


ALTER TABLE `rubric_templates`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 5. New table - rubric_template_grades
--

DROP TABLE IF EXISTS `rubric_template_grades`;
CREATE TABLE IF NOT EXISTS `rubric_template_grades` (
`id` int(11) NOT NULL,
  `education_grade_id` int(11) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `rubric_template_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `rubric_template_grades`
 ADD PRIMARY KEY (`id`), ADD KEY `education_grade_id` (`education_grade_id`), ADD KEY `rubric_template_id` (`rubric_template_id`);


ALTER TABLE `rubric_template_grades`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 6. New table - rubric_template_options
--

DROP TABLE IF EXISTS `rubric_template_options`;
CREATE TABLE IF NOT EXISTS `rubric_template_options` (
`id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `weighting` int(3) NOT NULL,
  `color` varchar(10) NOT NULL DEFAULT 'ffffff',
  `order` int(3) NOT NULL DEFAULT '0',
  `rubric_template_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `rubric_template_options`
 ADD PRIMARY KEY (`id`), ADD KEY `rubric_template_id` (`rubric_template_id`);


ALTER TABLE `rubric_template_options`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 7. New table - rubric_template_roles
--

DROP TABLE IF EXISTS `rubric_template_roles`;
CREATE TABLE IF NOT EXISTS `rubric_template_roles` (
  `id` char(36) NOT NULL,
  `rubric_template_id` int(11) NOT NULL,
  `security_role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `rubric_template_roles`
 ADD PRIMARY KEY (`id`), ADD KEY `rubric_template_id` (`rubric_template_id`), ADD KEY `security_role_id` (`security_role_id`);

--
-- 8. New table - rubric_sections
--

DROP TABLE IF EXISTS `rubric_sections`;
CREATE TABLE IF NOT EXISTS `rubric_sections` (
`id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `order` int(3) NOT NULL DEFAULT '0',
  `rubric_template_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `rubric_sections`
 ADD PRIMARY KEY (`id`), ADD KEY `rubric_template_id` (`rubric_template_id`);


ALTER TABLE `rubric_sections`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 9. New table - rubric_criterias
--

DROP TABLE IF EXISTS `rubric_criterias`;
CREATE TABLE IF NOT EXISTS `rubric_criterias` (
`id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `order` int(3) NOT NULL DEFAULT '0',
  `type` int(1) NOT NULL DEFAULT '1' COMMENT '1 -> Section Break, 2 -> Dropdown',
  `rubric_section_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;


ALTER TABLE `rubric_criterias`
 ADD PRIMARY KEY (`id`), ADD KEY `rubric_section_id` (`rubric_section_id`);


ALTER TABLE `rubric_criterias`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 10. New table - rubric_criteria_options
--

DROP TABLE IF EXISTS `rubric_criteria_options`;
CREATE TABLE IF NOT EXISTS `rubric_criteria_options` (
`id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `rubric_template_option_id` int(11) NOT NULL,
  `rubric_criteria_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `rubric_criteria_options`
 ADD PRIMARY KEY (`id`), ADD KEY `rubric_template_option_id` (`rubric_template_option_id`), ADD KEY `rubric_criteria_id` (`rubric_criteria_id`);


ALTER TABLE `rubric_criteria_options`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 11. New table - quality_statuses
--

DROP TABLE IF EXISTS `quality_statuses`;
CREATE TABLE IF NOT EXISTS `quality_statuses` (
`id` int(11) NOT NULL,
  `date_enabled` date NOT NULL,
  `date_disabled` date NOT NULL,
  `status` int(2) NOT NULL DEFAULT '1',
  `academic_period_id` int(11) NOT NULL,
  `rubric_template_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `quality_statuses`
 ADD PRIMARY KEY (`id`), ADD KEY `academic_period_id` (`academic_period_id`), ADD KEY `rubric_template_id` (`rubric_template_id`);


ALTER TABLE `quality_statuses`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 12. New table - institution_site_quality_rubrics
--

DROP TABLE IF EXISTS `institution_site_quality_rubrics`;
CREATE TABLE IF NOT EXISTS `institution_site_quality_rubrics` (
`id` int(11) NOT NULL,
  `comment` text,
  `rubric_template_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `education_grade_id` int(11) NOT NULL,
  `institution_site_section_id` int(11) NOT NULL,
  `institution_site_class_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_site_quality_rubrics`
 ADD PRIMARY KEY (`id`), ADD KEY `rubric_template_id` (`rubric_template_id`), ADD KEY `academic_period_id` (`academic_period_id`), ADD KEY `education_grade_id` (`education_grade_id`), ADD KEY `institution_site_section_id` (`institution_site_section_id`), ADD KEY `institution_site_class_id` (`institution_site_class_id`), ADD KEY `staff_id` (`staff_id`), ADD KEY `institution_site_id` (`institution_site_id`);


ALTER TABLE `institution_site_quality_rubrics`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 13. New table - institution_site_quality_rubric_answers
--

DROP TABLE IF EXISTS `institution_site_quality_rubric_answers`;
CREATE TABLE IF NOT EXISTS `institution_site_quality_rubric_answers` (
`id` int(11) NOT NULL,
  `institution_site_quality_rubric_id` int(11) NOT NULL,
  `rubric_section_id` int(11) NOT NULL,
  `rubric_criteria_id` int(11) NOT NULL,
  `rubric_criteria_option_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_site_quality_rubric_answers`
 ADD PRIMARY KEY (`id`), ADD KEY `institution_site_quality_rubric_id` (`institution_site_quality_rubric_id`), ADD KEY `rubric_section_id` (`rubric_section_id`), ADD KEY `rubric_criteria_id` (`rubric_criteria_id`), ADD KEY `rubric_criteria_option_id` (`rubric_criteria_option_id`);


ALTER TABLE `institution_site_quality_rubric_answers`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 14. New table - institution_site_quality_visits
--

DROP TABLE IF EXISTS `institution_site_quality_visits`;
CREATE TABLE IF NOT EXISTS `institution_site_quality_visits` (
`id` int(11) NOT NULL,
  `date` date NOT NULL,
  `comment` text,
  `quality_visit_type_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `education_grade_id` int(11) NOT NULL,
  `institution_site_section_id` int(11) NOT NULL,
  `institution_site_class_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_site_quality_visits`
 ADD PRIMARY KEY (`id`), ADD KEY `academic_period_id` (`academic_period_id`), ADD KEY `education_grade_id` (`education_grade_id`), ADD KEY `institution_site_section_id` (`institution_site_section_id`), ADD KEY `institution_site_class_id` (`institution_site_class_id`), ADD KEY `staff_id` (`staff_id`), ADD KEY `institution_site_id` (`institution_site_id`);


ALTER TABLE `institution_site_quality_visits`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 15. New table - institution_site_quality_visit_attachments
--

DROP TABLE IF EXISTS `institution_site_quality_visit_attachments`;
CREATE TABLE IF NOT EXISTS `institution_site_quality_visit_attachments` (
`id` int(11) NOT NULL,
  `file_name` varchar(200) DEFAULT NULL,
  `file_content` longblob,
  `visible` int(1) NOT NULL DEFAULT '1',
  `institution_site_quality_visit_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_site_quality_visit_attachments`
 ADD PRIMARY KEY (`id`), ADD KEY `institution_site_quality_visit_id` (`institution_site_quality_visit_id`);


ALTER TABLE `institution_site_quality_visit_attachments`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
