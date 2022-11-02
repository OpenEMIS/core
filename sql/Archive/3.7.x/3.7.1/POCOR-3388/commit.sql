-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3388', NOW());

-- institution_students
ALTER TABLE `institution_students` 
ADD `previous_institution_student_id` CHAR(36) CHARACTER SET utf8 COLLATE utf8_general_ci NULL 
AFTER `institution_id`, 
ADD INDEX (`previous_institution_student_id`);

-- creating temp table
UPDATE `institution_students`
SET `start_date` = '1970-01-01'
WHERE `start_date` = '0000-00-00';

UPDATE `institution_students`
SET `end_date` = '1970-01-01'
WHERE `end_date` = '0000-00-00';

UPDATE `institution_students`
SET `created` = '1970-01-01'
WHERE `created` = '0000-00-00 00:00:00';

DROP TABLE IF EXISTS `institution_students_tmp`;
CREATE TABLE IF NOT EXISTS `institution_students_tmp` (
  `id` char(36) NOT NULL,
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `start_date` date NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='This table contains information of all students in every institution';

ALTER TABLE `institution_students_tmp`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

INSERT INTO `institution_students_tmp` 
SELECT `id`, `student_id`, `start_date`, `created` 
FROM `institution_students`;

UPDATE `institution_students` `A`
SET `A`.`previous_institution_student_id` = (
	SELECT `id`
	FROM `institution_students_tmp` `B`
	WHERE `A`.`student_id` = `B`.`student_id`
	AND `A`.`start_date` > `B`.`start_date`
    ORDER BY `start_date` DESC
	LIMIT 1
);

DROP TABLE IF EXISTS `institution_students_tmp`;