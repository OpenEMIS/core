-- competency_grading_types
DROP TABLE IF EXISTS `competency_grading_types`;
CREATE TABLE IF NOT EXISTS `competency_grading_types` (
  `id` int(11) NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pass_mark` decimal(6,2) NOT NULL,
  `max` decimal(6,2) NOT NULL,
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

-- system_patches
DELETE FROM `system_patches` WHERE 'issue' = 'POCOR-3342';
