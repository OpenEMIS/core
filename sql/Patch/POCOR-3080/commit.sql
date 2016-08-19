-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3080', NOW());

-- assessment_items_grading_types
DROP TABLE IF EXISTS `assessment_items_grading_types`;
CREATE TABLE `assessment_items_grading_types` (
  `id` char(36) NOT NULL,
  `assessment_item_id` int(11) NOT NULL,
  `assessment_grading_type_id` int(11) NOT NULL,
  `assessment_id` int(11) NOT NULL,
  `assessment_period_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`assessment_grading_type_id`,`assessment_id`,`assessment_item_id`,`assessment_period_id`),
  UNIQUE(`id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- assessment_items

-- backup assessment_items / assessment_grading_type_id cloumn
RENAME TABLE `assessment_items` TO `z_3080_assessment_items`;

CREATE TABLE IF NOT EXISTS `assessment_items` (
  `id` int(11) NOT NULL,
  `weight` decimal(6,2) NOT NULL DEFAULT '0.00',
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

-- AUTO_INCREMENT for table `assessment_items`
ALTER TABLE `assessment_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- restore from backup
INSERT INTO `assessment_items` (`weight`, `assessment_id`, `education_subject_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `weight`, `assessment_id`, `education_subject_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3080_assessment_items`;