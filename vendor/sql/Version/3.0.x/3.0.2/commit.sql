-- PHPOE-1647

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
-- end PHPOE-1647

-- PHPOE-1664
UPDATE `field_options` SET `visible` = '1' WHERE `field_options`.`code` = 'StaffStatuses';
-- end PHPOE-1664

-- DB version
UPDATE `config_items` SET `value` = '3.0.2' WHERE `code` = 'db_version';
-- end DB version
