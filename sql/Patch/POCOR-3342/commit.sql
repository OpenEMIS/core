-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3342', NOW());

-- competency_grading_types
DROP TABLE IF EXISTS `competency_grading_types`;
CREATE TABLE IF NOT EXISTS `competency_grading_types` (
  `id` int(11) NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of grading types that can be used for an assessable competency';

ALTER TABLE `competency_grading_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `code` (`code`),
  ADD KEY `modified_user_id` (`modified_user_id`),
  ADD KEY `created_user_id` (`created_user_id`);

ALTER TABLE `competency_grading_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- competency_grading_options
DROP TABLE IF EXISTS `competency_grading_options`;
CREATE TABLE IF NOT EXISTS `competency_grading_options` (
  `id` int(11) NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `competency_grading_type_id` int(11) NOT NULL COMMENT 'links to competency_grading_types.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains all options linked to a specific grading type for competency';

ALTER TABLE `competency_grading_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `competency_grading_type_id` (`competency_grading_type_id`),
  ADD KEY `modified_user_id` (`modified_user_id`),
  ADD KEY `created_user_id` (`created_user_id`);

ALTER TABLE `competency_grading_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


-- Table structure for table `competency_templates`
DROP TABLE IF EXISTS `competency_templates`;
CREATE TABLE IF NOT EXISTS `competency_templates` (
  `id` int(11) NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `education_grade_id` int(11) NOT NULL COMMENT 'links to education_grades.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the competency template for a specific grade';

ALTER TABLE `competency_templates`
  ADD PRIMARY KEY (`id`, `academic_period_id`),
  ADD KEY `education_grade_id` (`education_grade_id`),
  ADD KEY `modified_user_id` (`modified_user_id`),
  ADD KEY `created_user_id` (`created_user_id`);

ALTER TABLE `competency_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Table structure for table `competency_items`
DROP TABLE IF EXISTS `competency_items`;
CREATE TABLE IF NOT EXISTS `competency_items` (
  `id` int(11) NOT NULL,
  `name` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `competency_template_id` int(11) NOT NULL COMMENT 'links to competency_templates.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `competency_items`
  ADD PRIMARY KEY (`id`, `academic_period_id`),
  ADD KEY `competency_template_id` (`competency_template_id`),
  ADD KEY `modified_user_id` (`modified_user_id`),
  ADD KEY `created_user_id` (`created_user_id`);

ALTER TABLE `competency_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Table structure for table `competency_criterias`
DROP TABLE IF EXISTS `competency_criterias`;
CREATE TABLE IF NOT EXISTS `competency_criterias` (
  `id` int(11) NOT NULL,
  `name` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `competency_item_id` int(11) NOT NULL COMMENT 'links to competency_items.id',  
  `competency_grading_type_id` int(11) NOT NULL COMMENT 'links to competency_grading_types.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `competency_criterias`
  ADD PRIMARY KEY (`id`, `academic_period_id`),
  ADD KEY `competency_item_id` (`competency_item_id`),
  ADD KEY `competency_grading_type_id` (`competency_grading_type_id`),
  ADD KEY `modified_user_id` (`modified_user_id`),
  ADD KEY `created_user_id` (`created_user_id`);

ALTER TABLE `competency_criterias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Table structure for table `competency_periods`
DROP TABLE IF EXISTS `competency_periods`;
CREATE TABLE IF NOT EXISTS `competency_periods` (
  `id` int(11) NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `date_enabled` date NOT NULL,
  `date_disabled` date NOT NULL,
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `competency_template_id` int(11) NOT NULL COMMENT 'links to competency_templates.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of periods for a specific competency';

ALTER TABLE `competency_periods`
  ADD PRIMARY KEY (`id`, `academic_period_id`),
  ADD KEY `competency_template_id` (`competency_template_id`),
  ADD KEY `modified_user_id` (`modified_user_id`),
  ADD KEY `created_user_id` (`created_user_id`);

ALTER TABLE `competency_periods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Table structure for table `competency_items_periods`
DROP TABLE IF EXISTS `competency_items_periods`;
CREATE TABLE IF NOT EXISTS `competency_items_periods` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `competency_item_id` int(11) NOT NULL COMMENT 'links to competency_templates.id',
  `competency_period_id` int(11) NOT NULL COMMENT 'links to competency_periods.id',
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `competency_items_periods`
  ADD PRIMARY KEY (`competency_item_id`,`competency_period_id`,`academic_period_id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `modified_user_id` (`modified_user_id`),
  ADD KEY `created_user_id` (`created_user_id`),
  ADD KEY `competency_item_id` (`competency_item_id`),
  ADD KEY `competency_period_id` (`competency_period_id`),
  ADD KEY `academic_period_id` (`academic_period_id`);

-- Table structure for table `student_competency_results`
DROP TABLE IF EXISTS `student_competency_results`;
CREATE TABLE IF NOT EXISTS `student_competency_results` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `competency_grading_option_id` int(11) DEFAULT NULL,
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `competency_template_id` int(11) NOT NULL COMMENT 'links to competency_templates.id',
  `competency_item_id` int(11) NOT NULL COMMENT 'links to competency_items.id',
  `competency_criteria_id` int(11) NOT NULL COMMENT 'links to competency_criterias.id',
  `competency_period_id` int(11) NOT NULL COMMENT 'links to competency_periods.id',
  `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains all the competency results for an individual student in an institution';

ALTER TABLE `student_competency_results`
  ADD PRIMARY KEY (`student_id`, `competency_template_id`, `competency_item_id`, `competency_criteria_id`, `competency_period_id`, `institution_id`, `academic_period_id`),
  ADD KEY `competency_grading_option_id` (`competency_grading_option_id`),
  ADD KEY `modified_user_id` (`modified_user_id`),
  ADD KEY `created_user_id` (`created_user_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `competency_template_id` (`competency_template_id`),
  ADD KEY `competency_item_id` (`competency_item_id`),
  ADD KEY `competency_criteria_id` (`competency_criteria_id`),
  ADD KEY `competency_period_id` (`competency_period_id`),
  ADD KEY `institution_id` (`institution_id`),
  ADD KEY `academic_period_id` (`academic_period_id`);

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
VALUES ('0e77e3d5-e39d-11e6-a064-525400b263eb', 'Criterias', 'competency_grading_type_id', 'Administration -> Competencies -> Criterias', 'Criteria Grading Type', NULL, NULL, '1', NULL, NULL, '1', '2017-01-26 00:00:00');

-- security_functions
UPDATE `security_functions`
SET `order` = `order` + 5
WHERE `order` >= 5056 AND `order` < 6000;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
VALUES
(5061, 'Templates', 'Competencies', 'Administration', 'Competencies', 5000, 'Templates.index|Templates.view', 'Templates.edit', 'Templates.add', 'Templates.remove', NULL, 5056, 1, NULL, NULL, NULL, 1, '2017-01-27 00:00:00'),
(5062, 'Items', 'Competencies', 'Administration', 'Competencies', 5000, 'Items.index|Items.view', 'Items.edit', 'Items.add', 'Items.remove', NULL, 5057, 1, NULL, NULL, NULL, 1, '2017-01-27 00:00:00'),
(5063, 'Criterias', 'Competencies', 'Administration', 'Competencies', 5000, 'Criterias.index|Criterias.view', 'Criterias.edit', 'Criterias.add', 'Criterias.remove', NULL, 5058, 1, NULL, NULL, NULL, 1, '2017-01-27 00:00:00'),
(5064, 'Periods', 'Competencies', 'Administration', 'Competencies', 5000, 'Periods.index|Periods.view', 'Periods.edit', 'Periods.add', 'Periods.remove', NULL, 5059, 1, NULL, NULL, NULL, 1, '2017-01-27 00:00:00'),
(5065, 'GradingTypes', 'Competencies', 'Administration', 'Competencies', 5000, 'GradingTypes.index|GradingTypes.view', 'GradingTypes.edit', 'GradingTypes.add', 'GradingTypes.remove', NULL, 5060, 1, NULL, NULL, NULL, 1, '2017-01-27 00:00:00');

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
VALUES
(1053, 'Competency Results', 'Institutions', 'Institutions', 'Students', 8, 'StudentCompetencies.index|StudentCompetencies.view|StudentCompetencyResults.viewResults', 'StudentCompetencies.edit|StudentCompetencyResults.edit', 'StudentCompetencies.add|StudentCompetencyResults.add', NULL, NULL, 1054, 1, NULL, NULL, NULL, 1, '2017-01-27 00:00:00');
