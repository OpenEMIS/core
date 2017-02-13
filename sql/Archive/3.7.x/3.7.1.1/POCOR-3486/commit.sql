-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3486', NOW());

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