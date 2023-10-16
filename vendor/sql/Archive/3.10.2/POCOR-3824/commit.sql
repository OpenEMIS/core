-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3824', NOW());

-- Table structure for table `training_need_competencies`
DROP TABLE IF EXISTS `training_need_competencies`;
CREATE TABLE IF NOT EXISTS `training_need_competencies` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `editable` int(1) NOT NULL DEFAULT '1',
  `default` int(1) NOT NULL DEFAULT '0',
  `international_code` varchar(50) DEFAULT NULL,
  `national_code` varchar(50) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `training_need_competencies`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `training_need_competencies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Table structure for table `training_need_standards`
DROP TABLE IF EXISTS `training_need_standards`;
CREATE TABLE IF NOT EXISTS `training_need_standards` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `editable` int(1) NOT NULL DEFAULT '1',
  `default` int(1) NOT NULL DEFAULT '0',
  `international_code` varchar(50) DEFAULT NULL,
  `national_code` varchar(50) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `training_need_standards`
  ADD PRIMARY KEY (`id`);
  
ALTER TABLE `training_need_standards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Table structure for table `training_need_sub_standards`
DROP TABLE IF EXISTS `training_need_sub_standards`;
CREATE TABLE IF NOT EXISTS `training_need_sub_standards` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `editable` int(1) NOT NULL DEFAULT '1',
  `default` int(1) NOT NULL DEFAULT '0',
  `international_code` varchar(50) DEFAULT NULL,
  `national_code` varchar(50) DEFAULT NULL,
  `training_need_standard_id` int(11) NOT NULL COMMENT 'links to training_need_standards.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `training_need_sub_standards`
  ADD PRIMARY KEY (`id`);
  
ALTER TABLE `training_need_sub_standards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Table structure for table `staff_training_needs`
RENAME TABLE `staff_training_needs` TO `z_3824_staff_training_needs`;

DROP TABLE IF EXISTS `staff_training_needs`;
CREATE TABLE IF NOT EXISTS `staff_training_needs` (
  `id` int(11) NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(20) NOT NULL,
  `training_course_id` int(11) NOT NULL COMMENT 'links to training_courses.id',
  `training_need_category_id` int(11) NOT NULL COMMENT 'links to training_need_categories.id',
  `training_need_competency_id` int(11) NOT NULL COMMENT 'links to training_need_competencies.id',
  `training_need_sub_standard_id` int(11) NOT NULL COMMENT 'links to training_need_sub_standards.id',
  `training_priority_id` int(11) NOT NULL COMMENT 'links to training_priorities.id',
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `assignee_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to security_users.id',
  `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `staff_training_needs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `training_course_id` (`training_course_id`),
  ADD KEY `training_need_category_id` (`training_need_category_id`),
  ADD KEY `training_priority_id` (`training_priority_id`),
  ADD KEY `training_need_competency_id` (`training_need_competency_id`),
  ADD KEY `training_need_sub_standard_id` (`training_need_sub_standard_id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `assignee_id` (`assignee_id`),
  ADD KEY `status_id` (`status_id`),
  ADD KEY `modified_user_id` (`modified_user_id`),
  ADD KEY `created_user_id` (`created_user_id`);

ALTER TABLE `staff_training_needs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

INSERT INTO `staff_training_needs` (`id`, `reason`, `type`, `training_course_id`, `training_need_category_id`, `training_need_competency_id`, `training_need_sub_standard_id`, `training_priority_id`, `staff_id`, `assignee_id`, `status_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `comments`, (IF(`course_id` > 0, 'CATALOGUE', 'NEED')), `course_id`, `training_need_category_id`, 0, 0, `training_priority_id`, `staff_id`, `assignee_id`, `status_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3824_staff_training_needs`;
