ALTER TABLE `institution_staff`
RENAME TO  `z_2828_institution_staff` ;

CREATE TABLE `institution_staff` (
  `id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
  `institution_position_id` int(11) NOT NULL COMMENT 'links to institution_positions.id',
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `FTE` decimal(5,2) DEFAULT NULL,
  `start_date` date NOT NULL,
  `start_year` int(4) NOT NULL,
  `end_date` date DEFAULT NULL,
  `end_year` int(4) DEFAULT NULL,
  `staff_type_id` int(5) NOT NULL COMMENT 'links to staff_types.id',
  `staff_status_id` int(3) NOT NULL COMMENT 'links to staff_statuses.id',
  `security_group_user_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'links to security_group_users.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`academic_period_id`,`institution_id`,`institution_position_id`,`staff_id`,`start_date`),
  KEY `id` (`id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `staff_type_id` (`staff_type_id`),
  KEY `staff_status_id` (`staff_status_id`),
  KEY `institution_position_id` (`institution_position_id`),
  KEY `staff_id` (`staff_id`),
  KEY `institution_id` (`institution_id`),
  KEY `security_group_user_id` (`security_group_user_id`),
  KEY `start_date` (`start_date`),
  KEY `end_date` (`end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains information of all staff in every institution'
PARTITION BY HASH (`staff_id`)
PARTITIONS 101;

INSERT INTO `institution_staff`
SELECT
    `z_2828_institution_staff`.`id`,
    `academic_periods`.`id`,
    `z_2828_institution_staff`.`institution_id`,
    `z_2828_institution_staff`.`institution_position_id`,
    `z_2828_institution_staff`.`staff_id`,
    `z_2828_institution_staff`.`FTE`,
    `z_2828_institution_staff`.`start_date`,
    `z_2828_institution_staff`.`start_year`,
    `z_2828_institution_staff`.`end_date`,
    `z_2828_institution_staff`.`end_year`,
    `z_2828_institution_staff`.`staff_type_id`,
    `z_2828_institution_staff`.`staff_status_id`,
    `z_2828_institution_staff`.`security_group_user_id`,
    `z_2828_institution_staff`.`modified_user_id`,
    `z_2828_institution_staff`.`modified`,
    `z_2828_institution_staff`.`created_user_id`,
    `z_2828_institution_staff`.`created`
FROM `z_2828_institution_staff`
LEFT JOIN `academic_periods`
ON (
  `z_2828_institution_staff`.`start_date` >= `academic_periods`.`start_date`
  AND `z_2828_institution_staff`.`start_date` <= `academic_periods`.`end_date`
AND (`z_2828_institution_staff`.`end_date` >= `academic_periods`.`end_date` OR `z_2828_institution_staff`.`end_date` IS NULL)) OR (
  `z_2828_institution_staff`.`start_date` <= `academic_periods`.`start_date`
  AND `z_2828_institution_staff`.`end_date` IS NULL
)
WHERE `academic_periods`.`code` <> 'All';

ALTER TABLE `institution_positions`
RENAME TO  `z_2828_institution_positions` ;

CREATE TABLE `institution_positions` (
  `id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
  `position_no` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `staff_position_title_id` int(11) NOT NULL COMMENT 'links to staff_position_titles.id',
  `staff_position_grade_id` int(11) NOT NULL COMMENT 'links to staff_position_grades.id',
  `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
  `assignee_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to security_users.id',
  `is_homeroom` int(1) NOT NULL DEFAULT '1',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`, `academic_period_id`),
  KEY `id` (`id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `status_id` (`status_id`),
  KEY `staff_position_title_id` (`staff_position_title_id`),
  KEY `staff_position_grade_id` (`staff_position_grade_id`),
  KEY `institution_id` (`institution_id`),
  KEY `assignee_id` (`assignee_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of positions offered by the institutions';

INSERT IGNORE INTO `institution_positions` (`academic_period_id`, `id`, `status_id`, `position_no`, `staff_position_title_id`, `staff_position_grade_id`, `institution_id`, `assignee_id`, `is_homeroom`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `institution_staff`.`academic_period_id`, `z_2828_institution_positions`.`id`, `z_2828_institution_positions`.`status_id`, `z_2828_institution_positions`.`position_no`, `z_2828_institution_positions`.`staff_position_title_id`, `z_2828_institution_positions`.`staff_position_grade_id`, `z_2828_institution_positions`.`institution_id`, `z_2828_institution_positions`.`assignee_id`, `z_2828_institution_positions`.`is_homeroom`, `z_2828_institution_positions`.`modified_user_id`, `z_2828_institution_positions`.`modified`, `z_2828_institution_positions`.`created_user_id`, `z_2828_institution_positions`.`created`
FROM `z_2828_institution_positions`
inner join `institution_staff` ON `institution_staff`.`institution_position_id` = `z_2828_institution_positions`.`id`;

INSERT IGNORE INTO `institution_positions` (`academic_period_id`, `id`, `status_id`, `position_no`, `staff_position_title_id`, `staff_position_grade_id`, `institution_id`, `assignee_id`, `is_homeroom`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `academic_periods`.`id`, `z_2828_institution_positions`.`id`, `z_2828_institution_positions`.`status_id`, `z_2828_institution_positions`.`position_no`, `z_2828_institution_positions`.`staff_position_title_id`, `z_2828_institution_positions`.`staff_position_grade_id`, `z_2828_institution_positions`.`institution_id`, `z_2828_institution_positions`.`assignee_id`, `z_2828_institution_positions`.`is_homeroom`, `z_2828_institution_positions`.`modified_user_id`, `z_2828_institution_positions`.`modified`, `z_2828_institution_positions`.`created_user_id`, `z_2828_institution_positions`.`created`
FROM `z_2828_institution_positions`
LEFT JOIN `institution_positions` ON `institution_positions`.`id` = `z_2828_institution_positions`.`id`
INNER JOIN `academic_periods` ON `academic_periods`.`start_date` >= `z_2828_institution_positions`.`created` AND `academic_periods`.`end_date` <= `z_2828_institution_positions`.`created`;

INSERT IGNORE INTO `institution_positions` (`academic_period_id`, `id`, `status_id`, `position_no`, `staff_position_title_id`, `staff_position_grade_id`, `institution_id`, `assignee_id`, `is_homeroom`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `academic_periods`.`id`, `z_2828_institution_positions`.`id`, `z_2828_institution_positions`.`status_id`, `z_2828_institution_positions`.`position_no`, `z_2828_institution_positions`.`staff_position_title_id`, `z_2828_institution_positions`.`staff_position_grade_id`, `z_2828_institution_positions`.`institution_id`, `z_2828_institution_positions`.`assignee_id`, `z_2828_institution_positions`.`is_homeroom`, `z_2828_institution_positions`.`modified_user_id`, `z_2828_institution_positions`.`modified`, `z_2828_institution_positions`.`created_user_id`, `z_2828_institution_positions`.`created`
FROM `z_2828_institution_positions`
LEFT JOIN `institution_positions` ON `institution_positions`.`id` = `z_2828_institution_positions`.`id`
INNER JOIN `academic_periods` ON `academic_periods`.`current` = 1
WHERE `institution_positions`.`id` IS NULL;

