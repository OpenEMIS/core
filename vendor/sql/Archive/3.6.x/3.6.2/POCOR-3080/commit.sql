-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3080', NOW());

-- assessment_items_grading_types
DROP TABLE IF EXISTS `assessment_items_grading_types`;
CREATE TABLE IF NOT EXISTS `assessment_items_grading_types` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `education_subject_id` int(11) NOT NULL COMMENT 'links to education_subjects.id',
  `assessment_grading_type_id` int(11) NOT NULL COMMENT 'links to assessment_grading_types.id',
  `assessment_id` int(11) NOT NULL COMMENT 'links to assessments.id',
  `assessment_period_id` int(11) NOT NULL COMMENT 'links to assessment_periods.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes for table `assessment_items_grading_types`
ALTER TABLE `assessment_items_grading_types`
  ADD PRIMARY KEY (`assessment_grading_type_id`,`assessment_id`,`education_subject_id`,`assessment_period_id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `modified_user_id` (`modified_user_id`),
  ADD KEY `created_user_id` (`created_user_id`);

-- assessment_items
-- backup assessment_items / assessment_grading_type_id cloumn
RENAME TABLE `assessment_items` TO `z_3080_assessment_items`;

CREATE TABLE IF NOT EXISTS `assessment_items` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `weight` decimal(6,2) DEFAULT '0.00',
  `assessment_id` int(11) NOT NULL,
  `education_subject_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes for table `assessment_items`
ALTER TABLE `assessment_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assessment_id` (`assessment_id`),
  ADD KEY `education_subject_id` (`education_subject_id`),
  ADD KEY `modified_user_id` (`modified_user_id`),
  ADD KEY `created_user_id` (`created_user_id`);

-- restore from backup
INSERT INTO `assessment_items` (`id`, `weight`, `assessment_id`, `education_subject_id`,
  `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `weight`, `assessment_id`, `education_subject_id`,
  `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3080_assessment_items`;

INSERT INTO `assessment_items_grading_types` (`id`, `education_subject_id`, `assessment_grading_type_id`, `assessment_id`, `assessment_period_id`,
  `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT uuid(), AI.`education_subject_id`, AI.`assessment_grading_type_id`, AI.`assessment_id`, AP.`id`,
  AI.`modified_user_id`, AI.`modified`, AI.`created_user_id`, AI.`created`
FROM `z_3080_assessment_items`AI
INNER JOIN `assessment_periods` AP ON AP.`assessment_id` = AI.`assessment_id`;

-- assessment_periods
ALTER TABLE `assessment_periods` CHANGE `weight` `weight` DECIMAL(6,2) NULL DEFAULT '0.00';

-- for institution_shift POCOR-2602
ALTER TABLE `institution_shifts` CHANGE `shift_option_id` `shift_option_id` INT(11) NOT NULL COMMENT 'links to shift_options.id';