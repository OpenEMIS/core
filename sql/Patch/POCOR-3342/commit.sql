-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3342', NOW());

-- competency_grading_types
DROP TABLE IF EXISTS `competency_grading_types`;
CREATE TABLE IF NOT EXISTS `competency_grading_types` (
  `id` int(11) NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `result_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
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
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `point` decimal(6,2) DEFAULT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
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
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mandatory` int(1) NOT NULL DEFAULT '1',
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
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `percentage` decimal(6,2) DEFAULT NULL,
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
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `date_enabled` date NOT NULL,
  `date_disabled` date NOT NULL,
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `competency_template_id` int(11) NOT NULL COMMENT 'links to competency_templates.id',
  `competency_item_id` int(11) NOT NULL COMMENT 'links to competency_items.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of periods for a specific competency';

ALTER TABLE `competency_periods`
  ADD PRIMARY KEY (`id`, `academic_period_id`),
  ADD KEY `competency_template_id` (`competency_template_id`),
  ADD KEY `competency_item_id` (`competency_item_id`),
  ADD KEY `modified_user_id` (`modified_user_id`),
  ADD KEY `created_user_id` (`created_user_id`);

ALTER TABLE `competency_periods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Table structure for table `competency_results`
DROP TABLE IF EXISTS `competency_results`;
CREATE TABLE IF NOT EXISTS `competency_results` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `marks` decimal(6,2) DEFAULT NULL,
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

ALTER TABLE `competency_results`
  ADD PRIMARY KEY (`student_id`, `competency_template_id`, `competency_item_id`, `competency_criteria_id`, `competency_period_id`, `institution_id`, `academic_period_id`),
  ADD KEY `competency_grading_option_id` (`competency_grading_option_id`),
  ADD KEY `modified_user_id` (`modified_user_id`),
  ADD KEY `created_user_id` (`created_user_id`);
