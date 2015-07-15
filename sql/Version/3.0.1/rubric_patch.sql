-- 14th July 2015

-- patch rubric_statuses
TRUNCATE TABLE `rubric_statuses`;
INSERT INTO `rubric_statuses` (`id`, `date_enabled`, `date_disabled`, `status`, `rubric_template_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `date_enabled`, `date_disabled`, `status`, `rubric_template_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_1461_quality_statuses`;

-- patch rubric_status_periods
TRUNCATE TABLE `rubric_status_periods`;
INSERT INTO `rubric_status_periods` (`id`, `academic_period_id`, `rubric_status_id`)
SELECT `id`, `academic_period_id`, `quality_status_id`
FROM `z_1461_quality_status_periods`;

-- patch rubric_status_programmes
TRUNCATE TABLE `rubric_status_programmes`;
INSERT INTO `rubric_status_programmes` (`id`, `education_programme_id`, `rubric_status_id`)
SELECT `id`, `education_programme_id`, `quality_status_id`
FROM `z_1461_quality_status_programmes`;

-- patch rubric_status_roles
TRUNCATE TABLE `rubric_status_roles`;
INSERT INTO `rubric_status_roles` (`id`, `security_role_id`, `rubric_status_id`)
SELECT `id`, `security_role_id`, `quality_status_id`
FROM `z_1461_quality_status_roles`;
