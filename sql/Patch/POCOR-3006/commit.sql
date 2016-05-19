-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3006', NOW());

-- backup
ALTER TABLE `institution_positions` 
RENAME TO  `z_3006_institution_positions`;

-- create new table
CREATE TABLE IF NOT EXISTS `institution_positions` (
  `id` int(11) NOT NULL,
  `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
  `position_no` varchar(30) NOT NULL,
  `staff_position_title_id` int(11) NOT NULL,
  `staff_position_grade_id` int(11) NOT NULL,
  `institution_id` int(11) NOT NULL,
  `is_homeroom` int(1) NOT NULL DEFAULT '1',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `institution_positions`
--
ALTER TABLE `institution_positions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `institution_id` (`institution_id`),
  ADD KEY `staff_position_grade_id` (`staff_position_grade_id`),
  ADD KEY `staff_position_title_id` (`staff_position_title_id`),
  ADD KEY `status_id` (`status_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `institution_positions`
--
ALTER TABLE `institution_positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- reinsert from backup table

INSERT INTO `institution_positions`
SELECT `z_3006_institution_positions`.`id`, 
  `z_3006_institution_positions`.`status_id`, 
  `z_3006_institution_positions`.`position_no`, 
  `z_3006_institution_positions`.`staff_position_title_id`, 
  `z_3006_institution_positions`.`staff_position_grade_id`,
  `z_3006_institution_positions`.`institution_id`,
  `z_3006_institution_positions`.`is_homeroom`,
  `z_3006_institution_positions`.`modified_user_id`, 
  `z_3006_institution_positions`.`modified`, 
  `z_3006_institution_positions`.`created_user_id`, 
  `z_3006_institution_positions`.`created` 
FROM `z_3006_institution_positions`;

-- patch to remove blank space

UPDATE `institution_positions`
SET `position_no` = REPLACE(`position_no`, ' ', '');