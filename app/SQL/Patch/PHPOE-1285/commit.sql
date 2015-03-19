--
-- 1. Navigations
--

UPDATE `navigations` SET `plugin` = 'Quality', `controller` = 'QualityRubrics', `action` = 'index' , `pattern` = 'index|RubricTemplate|RubricSection|RubricCriteria' WHERE `module` = 'Administration' AND `controller` = 'Quality' AND `header` = 'Quality' AND `title` = 'Rubrics';
UPDATE `navigations` SET `plugin` = 'Quality', `controller` = 'QualityStatuses', `action` = 'index' , `pattern` = 'index|view|edit|add|delete' WHERE `module` = 'Administration' AND `controller` = 'Quality' AND `header` = 'Quality' AND `title` = 'Status';

--
-- 2. Security Functions
--

UPDATE `security_functions` SET `controller` = 'QualityRubrics', `_view` = 'index|view', `_edit` = '_view:edit' , `_add` = '_view:add', `_delete` = '_view:delete' WHERE `controller` = 'Quality' AND `module` = 'Administration' AND `category` = 'Quality' AND `name` = 'Rubrics';
UPDATE `security_functions` SET `controller` = 'QualityStatuses', `_view` = 'index|view', `_edit` = '_view:edit' , `_add` = '_view:add', `_delete` = '_view:delete' WHERE `controller` = 'Quality' AND `module` = 'Administration' AND `category` = 'Quality' AND `name` = 'Status';

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
  `rubrics_template_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `rubric_template_grades`
 ADD PRIMARY KEY (`id`), ADD KEY `education_grade_id` (`education_grade_id`);


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
`id` int(11) NOT NULL,
  `rubric_template_id` int(11) NOT NULL,
  `security_role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `rubric_template_roles`
 ADD PRIMARY KEY (`id`);


ALTER TABLE `rubric_template_roles`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `rubric_criterias`
 ADD PRIMARY KEY (`id`), ADD KEY `rubric_template_header_id` (`rubric_section_id`);


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
 ADD PRIMARY KEY (`id`), ADD KEY `rubrics_template_column_info_id` (`rubric_template_option_id`), ADD KEY `rubric_template_item_id` (`rubric_criteria_id`);


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
 ADD PRIMARY KEY (`id`), ADD KEY `rubric_template_id` (`rubric_template_id`);


ALTER TABLE `quality_statuses`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;