
CREATE TABLE IF NOT EXISTS `institution_grade_students` (
	`id` char(36) NOT NULL,
	`security_user_id` int(11) NOT NULL,
	`education_grade_id` int(11) NOT NULL,
	`academic_period_id` int(11) NOT NULL,
	`institution_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `institution_grade_students`
  ADD PRIMARY KEY (`id`),
  ADD INDEX (`security_user_id`),
  ADD INDEX (`education_grade_id`),
  ADD INDEX (`academic_period_id`),
  ADD INDEX (`institution_id`);
