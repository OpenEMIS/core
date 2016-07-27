-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3081', NOW());

-- backup institution_students
DROP TABLE IF EXISTS `z_3081_institution_students`;
CREATE TABLE IF NOT EXISTS `z_3081_institution_students` (
  `id` char(36) NOT NULL,
  `end_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Indexes for table `institution_students`
ALTER TABLE `z_3081_institution_students`
  ADD PRIMARY KEY (`id`);

-- patch the end_date for transfered records
UPDATE `institution_students` I1
INNER JOIN(
	SELECT * FROM `institution_students`
)I2 ON I2.`start_date` = I1.`end_date`
	AND I2.`student_id` = I1.`student_id` 
	AND I2.`start_date` <>  I2.`end_date` 
SET I1.`end_date` = DATE_ADD(I1.`end_date`, INTERVAL -1 DAY)
WHERE I1.`student_status_id` = 3