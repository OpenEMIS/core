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
  `security_group_user_id` char(36) DEFAULT NULL COMMENT 'links to security_group_users.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`academic_period_id`, `institution_id`, `institution_position_id`, `staff_id`),
  KEY `id` (`id`),
  KEY `staff_type_id` (`staff_type_id`),
  KEY `staff_status_id` (`staff_status_id`),
  KEY `institution_position_id` (`institution_position_id`),
  KEY `staff_id` (`staff_id`),
  KEY `institution_id` (`institution_id`),
  KEY `security_group_user_id` (`security_group_user_id`),
  KEY `start_date` (`start_date`),
  KEY `end_date` (`end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains information of all staff in every institution'
/*!50100 PARTITION BY HASH (`academic_period_id`)
PARTITIONS 8 */;

ALTER TABLE `institution_staff`
CHANGE COLUMN `id` `id` INT(11) NOT NULL ,
DROP PRIMARY KEY;

INSERT INTO institution_staff
SELECT
    z_2828_institution_staff.id,
    academic_periods.id,
    z_2828_institution_staff.institution_id,
    z_2828_institution_staff.institution_position_id,
    z_2828_institution_staff.staff_id,
    z_2828_institution_staff.FTE,
    z_2828_institution_staff.start_date,
    z_2828_institution_staff.start_year,
    z_2828_institution_staff.end_date,
    z_2828_institution_staff.end_year,
    z_2828_institution_staff.staff_type_id,
    z_2828_institution_staff.staff_status_id,
    z_2828_institution_staff.security_group_user_id,
    z_2828_institution_staff.modified_user_id,
    z_2828_institution_staff.modified,
    z_2828_institution_staff.created_user_id,
    z_2828_institution_staff.created
FROM z_2828_institution_staff
LEFT JOIN academic_periods
ON z_2828_institution_staff.start_date >= academic_periods.start_date
AND (z_2828_institution_staff.end_date >= academic_periods.end_date OR z_2828_institution_staff.end_date IS NULL);